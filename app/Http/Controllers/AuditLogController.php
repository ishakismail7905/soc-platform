<?php

namespace App\Http\Controllers;

use App\Http\Requests\AuditLogFilterRequest;
use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Support\AuditEvent;
use App\Support\CsvSafe;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Activity log viewer and extract (wireframe 15, FR-10). Read-only: there is no route that changes a row. */
class AuditLogController extends Controller
{
    public function __construct(private ActivityLogger $logger) {}

    public function index(AuditLogFilterRequest $request): View
    {
        $filters = $request->validated();
        $logs = $this->filtered($filters)->orderByDesc('id')->paginate(50)->withQueryString();

        return view('audit.index', [
            'logs' => $logs,
            'people' => $this->peopleFor($logs->getCollection()),
            'clients' => Client::orderBy('name')->get(['id', 'name', 'code']),
            'users' => User::orderBy('name')->get(['id', 'name', 'email']),
            'events' => AuditEvent::all(),
            'filters' => $filters,
        ]);
    }

    public function export(AuditLogFilterRequest $request): StreamedResponse
    {
        $filters = array_filter($request->validated(), fn ($value) => $value !== null);
        $query = $this->filtered($filters);

        // Freeze the extract: exactly the rows that exist now, so the file matches its log row.
        $lastId = (int) (clone $query)->max('id');
        $rows = (clone $query)->where('id', '<=', $lastId)->count();

        // Taking data out of the SOC is itself a security event: logged before anything leaves.
        $this->logger->log(AuditEvent::AUDIT_EXPORTED, null, ['filters' => $filters, 'row_count' => $rows]);

        $emails = User::pluck('email', 'id');
        $codes = Client::pluck('code', 'id');

        return response()->streamDownload(function () use ($query, $lastId, $emails, $codes) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['id', 'occurred_at', 'user_email', 'role', 'client', 'event', 'subject_type', 'subject_id', 'ip_address', 'properties', 'hash'], ',', '"', '');

            foreach ($query->where('id', '<=', $lastId)->lazyById(500) as $log) {
                fputcsv($out, array_map([CsvSafe::class, 'cell'], [
                    $log->id,
                    $log->occurred_at?->format('Y-m-d H:i:s'),
                    $log->user_id ? ($emails[$log->user_id] ?? '') : '',
                    $log->role,
                    $log->client_id ? ($codes[$log->client_id] ?? '') : '',
                    $log->event,
                    $log->subject_type,
                    $log->subject_id,
                    $log->ip_address,
                    $log->getRawOriginal('properties'),
                    $log->hash,
                ]), ',', '"', '');
            }

            fclose($out);
        }, 'activity-log-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function filtered(array $f): Builder
    {
        return ActivityLog::query()
            ->when($f['event'] ?? null, fn ($q, $event) => $q->where('event', $event))
            ->when($f['client_id'] ?? null, fn ($q, $id) => $q->where('client_id', $id))
            // A user's history = what they did + what was done to them (e.g. failed logins against them).
            ->when($f['user_id'] ?? null, fn ($q, $id) => $q->where(fn ($q) => $q
                ->where('user_id', $id)
                ->orWhere(fn ($q) => $q->where('subject_type', User::class)->where('subject_id', $id))))
            ->when($f['from'] ?? null, fn ($q, $d) => $q->where('occurred_at', '>=', Carbon::parse($d)->startOfDay()))
            ->when($f['to'] ?? null, fn ($q, $d) => $q->where('occurred_at', '<=', Carbon::parse($d)->endOfDay()));
    }

    /** Names for the User column in one query, for actors and target accounts. */
    private function peopleFor(Collection $logs): Collection
    {
        $ids = $logs->pluck('user_id')
            ->merge($logs->where('subject_type', User::class)->pluck('subject_id'))
            ->filter()
            ->unique();

        return User::whereIn('id', $ids)->get(['id', 'name', 'email'])->keyBy('id');
    }
}