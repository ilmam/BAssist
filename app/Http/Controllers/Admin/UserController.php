<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Support\EntityAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        EntityAccess::authorizeSuperAdmin(auth()->user());

        $users = User::query()
            ->with('role')
            ->orderBy('name')
            ->get();

        return view('pages.admin.users.index', compact('users'));
    }

    public function edit(User $user): View
    {
        EntityAccess::authorizeSuperAdmin(auth()->user());

        $roles = Role::query()->orderBy('name')->get();

        return view('pages.admin.users.form', compact('user', 'roles'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        EntityAccess::authorizeSuperAdmin(auth()->user());

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'role_id' => ['required', 'integer', Rule::exists('roles', 'id')],
        ]);

        if ($this->isLastSuperAdmin($user) && (int) $validated['role_id'] !== (int) $user->role_id) {
            return redirect()
                ->route('admin.users.edit', $user)
                ->withErrors(['role_id' => 'The last super admin cannot be assigned a different role.']);
        }

        $user->update($validated);

        return redirect()
            ->route('admin.users.index')
            ->with('status', 'User updated successfully.');
    }

    private function isLastSuperAdmin(User $user): bool
    {
        if (! $user->role?->isSuperAdmin()) {
            return false;
        }

        return User::query()
            ->where('role_id', $user->role_id)
            ->count() <= 1;
    }
}
