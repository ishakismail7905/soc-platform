<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Permission;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Models\Client;
use App\Models\User;
use App\Services\UserAdminService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct(private UserAdminService $users) {}

    public function index(): View
    {
        return view('admin.users.index', ['users' => $this->allUsers(), 'panel' => null]);
    }

    public function create(): View
    {
        return view('admin.users.index', [
            'users' => $this->allUsers(),
            'panel' => 'create',
            'clients' => Client::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        ['user' => $user, 'temporary_password' => $temporary] = $this->users->create($request->validated(), $request->user());

        return redirect()->route('admin.users.index')
            ->with('status', "Account created for {$user->email}.")
            ->with('temporary_password', $temporary)
            ->with('temporary_for', $user->email);
    }

    public function permissions(): View
    {
        return view('admin.users.permissions', ['roles' => Role::cases(), 'permissions' => Permission::cases()]);
    }

    private function allUsers()
    {
        return User::with('clients')->orderBy('name')->get();
    }
}