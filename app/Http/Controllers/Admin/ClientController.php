<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ClientRequest;
use App\Models\Client;
use App\Services\ClientAdminService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClientController extends Controller
{
    public function __construct(private ClientAdminService $clients) {}

    public function index(): View
    {
        return view('admin.clients.index', ['clients' => $this->allClients(), 'panel' => null]);
    }

    public function create(): View
    {
        return view('admin.clients.index', ['clients' => $this->allClients(), 'panel' => 'create']);
    }

    public function store(ClientRequest $request): RedirectResponse
    {
        $client = $this->clients->create($request->validated(), $request->user());

        return redirect()->route('admin.clients.index')->with('status', "Client {$client->name} added.");
    }

    public function edit(Client $client): View
    {
        return view('admin.clients.index', [
            'clients' => $this->allClients(),
            'panel' => 'edit',
            'editing' => $client,
            'members' => $client->users()->orderBy('name')->get(),
        ]);
    }

    public function update(ClientRequest $request, Client $client): RedirectResponse
    {
        $this->clients->update($client, $request->validated(), $request->user());

        return redirect()->route('admin.clients.edit', $client)->with('status', 'Client updated.');
    }

    public function deactivate(Request $request, Client $client): RedirectResponse
    {
        $this->clients->setActive($client, false, $request->user());

        return redirect()->route('admin.clients.edit', $client)
            ->with('status', 'Client deactivated. Its records are kept and it can no longer be assigned to users.');
    }

    public function reactivate(Request $request, Client $client): RedirectResponse
    {
        $this->clients->setActive($client, true, $request->user());

        return redirect()->route('admin.clients.edit', $client)->with('status', 'Client reactivated.');
    }

    private function allClients()
    {
        return Client::withCount([
            'users as analysts_count' => fn ($q) => $q->where('users.role', Role::SocAnalyst->value),
            'users as viewers_count' => fn ($q) => $q->where('users.role', Role::ClientViewer->value),
        ])->orderBy('name')->get();
    }
}