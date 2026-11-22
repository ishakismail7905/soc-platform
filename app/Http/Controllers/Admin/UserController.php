<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Permission;
use App\Enums\Role;
use App\Exceptions\UserAdminException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\Client;
use App\Models\User;
use App\Services\UserAdminService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
            'clients' => $this->activeClients(),
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

    public function edit(User $user): View
    {
        return view('admin.users.index', [
            'users' => $this->allUsers(),
            'panel' => 'edit',
            'editing' => $user->load('clients'),
            'clients' => $this->activeClients(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        return $this->run(fn () => $this->users->update($user, $request->validated(), $request->user()), $user, 'Account updated.');
    }

    public function deactivate(Request $request, User $user): RedirectResponse
    {
        return $this->run(fn () => $this->users->deactivate($user, $request->user()), $user, 'Account deactivated. Their sessions were ended.');
    }

    public function reactivate(Request $request, User $user): RedirectResponse
    {
        return $this->run(fn () => $this->users->reactivate($user, $request->user()), $user, 'Account reactivated.');
    }

    public function unlock(Request $request, User $user): RedirectResponse
    {
        return $this->run(fn () => $this->users->unlock($user, $request->user()), $user, 'Account unlocked.');
    }

    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        try {
            $temporary = $this->users->resetPassword($user, $request->user());
        } catch (UserAdminException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.users.edit', $user)
            ->with('status', 'Password reset. Their sessions were ended.')
            ->with('temporary_password', $temporary)
            ->with('temporary_for', $user->email);
    }

    public function resetTwoFactor(Request $request, User $user): RedirectResponse
    {
        return $this->run(fn () => $this->users->resetTwoFactor($user, $request->user()), $user, '2FA reset. They must enrol again at next sign-in.');
    }

    public function permissions(): View
    {
        return view('admin.users.permissions', ['roles' => Role::cases(), 'permissions' => Permission::cases()]);
    }

    /** Run an admin action; a rule violation becomes a red message instead of an error page. */
    private function run(callable $action, User $user, string $message): RedirectResponse
    {
        try {
            $action();
        } catch (UserAdminException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.users.edit', $user)->with('status', $message);
    }

    private function allUsers()
    {
        return User::with('clients')->orderBy('name')->get();
    }

    private function activeClients()
    {
        return Client::where('is_active', true)->orderBy('name')->get();
    }
}