<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * The only way to write to the activity log (FR-10, NFR-3).
 * Each entry stores a SHA-256 hash of its own fields plus the previous entry's hash.
 */
class ActivityLogger
{
    public const GENESIS_HASH = '0000000000000000000000000000000000000000000000000000000000000000';

    /** Fields covered by the hash, in a fixed order. */
    public const HASHED_FIELDS = [
        'occurred_at', 'user_id', 'role', 'client_id', 'event',
        'subject_type', 'subject_id', 'ip_address', 'user_agent', 'properties',
    ];

    public function log(string $event, ?Model $subject = null, array $properties = [], ?User $actor = null): ActivityLog
    {
        $actor ??= auth()->user();
        $request = request();
        $userAgent = $request?->userAgent();

        $row = [
            'occurred_at' => now()->startOfSecond()->format('Y-m-d H:i:s'),
            'user_id' => $actor?->getKey(),
            'role' => $actor?->role?->value,
            'client_id' => $properties['client_id']
                ?? ($subject instanceof Client ? $subject->getKey() : $subject?->getAttribute('client_id')),
            'event' => $event,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'ip_address' => $request?->ip(),
            'user_agent' => $userAgent ? mb_substr($userAgent, 0, 255) : null,
            'properties' => $this->canonicalJson($properties),
        ];

        // Lock the newest row so two simultaneous writers can't both chain onto it.
        return DB::transaction(function () use ($row) {
            $prev = DB::table('activity_logs')->orderByDesc('id')->lockForUpdate()->value('hash')
                ?? self::GENESIS_HASH;

            $row['prev_hash'] = $prev;
            $row['hash'] = $this->hashRow($prev, $row);

            $log = new ActivityLog;
            $log->setRawAttributes($row); // store exactly the text that was hashed
            $log->save();

            return $log;
        });
    }

    /** Also used by soc:audit-verify, so both sides hash identically. */
    public function hashRow(string $prevHash, array $row): string
    {
        $parts = [];
        foreach (self::HASHED_FIELDS as $field) {
            $parts[] = (string) ($row[$field] ?? '');
        }

        // json_encode keeps field boundaries unambiguous (a value containing '|' can't fake another field).
        return hash('sha256', $prevHash . json_encode($parts, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    /** Keys sorted recursively, so the same data always gives the same text and the same hash. */
    private function canonicalJson(array $data): string
    {
        $sort = function ($value) use (&$sort) {
            if (! is_array($value)) {
                return $value;
            }
            if (! array_is_list($value)) {
                ksort($value);
            }

            return array_map($sort, $value);
        };

        return json_encode($sort($data), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }
}