<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Support\EntityAccess;
use App\Support\RolePermissionSync;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function index(): View
    {
        EntityAccess::authorizeSuperAdmin(auth()->user());

        $roles = Role::query()
            ->withCount('users')
            ->orderBy('name')
            ->get();

        return view('pages.admin.roles.index', compact('roles'));
    }

    public function create(): View
    {
        EntityAccess::authorizeSuperAdmin(auth()->user());

        $role = new Role;
        $permissionMatrix = RolePermissionSync::matrixFor($role);

        return view('pages.admin.roles.form', [
            'role' => $role,
            'permissionMatrix' => $permissionMatrix,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        EntityAccess::authorizeSuperAdmin(auth()->user());

        $validated = $this->validateRole($request);

        $role = Role::create([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
        ]);

        RolePermissionSync::sync($role, $request->input('permissions', []));

        return redirect()
            ->route('admin.roles.index')
            ->with('status', 'Role created successfully.');
    }

    public function edit(Role $role): View
    {
        EntityAccess::authorizeSuperAdmin(auth()->user());

        $role->load('entityPermissions');
        $permissionMatrix = RolePermissionSync::matrixFor($role);

        return view('pages.admin.roles.form', [
            'role' => $role,
            'permissionMatrix' => $permissionMatrix,
        ]);
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        EntityAccess::authorizeSuperAdmin(auth()->user());

        $validated = $this->validateRole($request, $role);

        $role->update([
            'name' => $validated['name'],
            'slug' => $role->isSuperAdmin() ? $role->slug : $validated['slug'],
        ]);

        RolePermissionSync::sync($role, $request->input('permissions', []));

        return redirect()
            ->route('admin.roles.index')
            ->with('status', 'Role updated successfully.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        EntityAccess::authorizeSuperAdmin(auth()->user());

        if ($role->isSuperAdmin()) {
            return redirect()
                ->route('admin.roles.index')
                ->withErrors(['role' => 'The super admin role cannot be deleted.']);
        }

        if ($role->users()->exists()) {
            return redirect()
                ->route('admin.roles.index')
                ->withErrors(['role' => 'This role is assigned to users and cannot be deleted.']);
        }

        $role->entityPermissions()->delete();
        $role->delete();

        return redirect()
            ->route('admin.roles.index')
            ->with('status', 'Role deleted successfully.');
    }

    /**
     * @return array{name: string, slug: string}
     */
    private function validateRole(Request $request, ?Role $role = null): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
        ];

        if ($role?->isSuperAdmin()) {
            $rules['slug'] = ['prohibited'];
        } else {
            $rules['slug'] = ['nullable', 'string', 'max:255', 'alpha_dash', 'unique:roles,slug'.($role ? ','.$role->id : '')];
        }

        $validated = $request->validate($rules);
        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['name']);

        return $validated;
    }
}
