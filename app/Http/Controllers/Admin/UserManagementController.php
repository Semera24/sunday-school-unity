<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Http\Requests\Admin\UpdateUserRolesRequest;
use App\Models\User;
use App\Notifications\SystemMessageNotification;
use App\Support\ActivityLogger;
use App\Support\SystemRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserManagementController extends Controller
{
    /**
     * Show the users management page.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $search = $request->string('search')->trim()->toString();

        return Inertia::render('admin/Users/Index', [
            'users' => User::query()
                ->with('roles')
                ->when($search !== '', function ($query) use ($search): void {
                    $query->searchLike(['name', 'email'], $search);
                })
                ->orderBy('name')
                ->paginate(10)
                ->withQueryString()
                ->through(fn (User $user): array => $this->userSummary($user, $request)),
            'filters' => [
                'search' => $search,
            ],
            'roles' => $this->roleOptions($request),
        ]);
    }

    /**
     * Show the user creation page.
     */
    public function create(Request $request): Response
    {
        $this->authorize('create', User::class);

        return Inertia::render('admin/Users/Create', [
            'roles' => $this->roleOptions($request),
        ]);
    }

    /**
     * Store a new user.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $user = User::query()->create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'password' => Hash::make($request->validated('password')),
        ]);

        $user->syncRoles($request->validated('roles', []));
        $user->notify(new SystemMessageNotification(
            title: 'Your account was created',
            message: 'An administrator created your account and assigned your initial access.',
            actionUrl: '/dashboard',
            actionLabel: 'Open dashboard',
        ));

        ActivityLogger::record(
            actor: $request->user(),
            event: 'users.created',
            description: "Created user {$user->email}.",
            subject: $user,
            properties: [
                'roles' => $request->validated('roles', []),
            ],
            request: $request,
        );

        return to_route('users.edit', $user)->with('success', 'User created successfully.');
    }

    /**
     * Show the user edit page.
     */
    public function edit(Request $request, User $user): Response
    {
        if ($user->hasBusinessIdentity()) {
            ActivityLogger::record(
                actor: $request->user(),
                event: 'users.business-edit-denied',
                description: "Denied technical edit screen access for business identity {$user->email}.",
                subject: $user,
                request: $request,
            );

            abort(403, 'Technical operators cannot inspect or modify Sunday School business identities.');
        }

        $this->authorize('update', $user);

        $user->load('roles');

        return Inertia::render('admin/Users/Edit', [
            'user' => $this->userSummary($user, $request),
            'roles' => $this->roleOptions($request),
        ]);
    }

    /**
     * Update the selected user's details.
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        if ($user->hasBusinessIdentity()) {
            ActivityLogger::record(
                actor: $request->user(),
                event: 'users.business-takeover-denied',
                description: "Denied technical password/email/profile mutation for business identity {$user->email}.",
                subject: $user,
                request: $request,
            );

            abort(403, 'Technical operators cannot modify passwords, emails, or profile details of Sunday School business identities.');
        }

        $this->authorize('update', $user);

        $validated = $request->validated();

        if ($user->isProtectedDemoPersona() && (
            ($validated['password'] !== null && $validated['password'] !== '')
            || strtolower((string) $validated['email']) !== strtolower((string) $user->email)
        )) {
            abort(403, 'Protected demo persona email and password credentials cannot be overwritten through technical administration.');
        }

        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        if ($validated['password'] !== null && $validated['password'] !== '') {
            $user->password = Hash::make($validated['password']);
        }

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        ActivityLogger::record(
            actor: $request->user(),
            event: 'users.updated',
            description: "Updated user {$user->email}.",
            subject: $user,
            properties: [
                'name' => $user->name,
                'email' => $user->email,
            ],
            request: $request,
        );

        return to_route('users.edit', $user)->with('success', 'User details updated successfully.');
    }

    /**
     * Update the selected user's assigned roles.
     */
    public function updateRoles(UpdateUserRolesRequest $request, User $user): RedirectResponse
    {
        if ($user->hasBusinessIdentity()) {
            ActivityLogger::record(
                actor: $request->user(),
                event: 'users.business-role-grant-denied',
                description: "Denied technical role assignment on business identity {$user->email}.",
                subject: $user,
                request: $request,
            );

            abort(403, 'Technical platform roles cannot be granted to or modified on Sunday School business identities.');
        }

        $this->authorize('updateRoles', $user);

        $roles = $request->validated('roles', []);

        if ($request->user()?->is($user)
            && $request->user()?->hasRole(SystemRole::SUPER_ADMIN)
            && ! in_array(SystemRole::SUPER_ADMIN, $roles, true)
        ) {
            return to_route('users.edit', $user)->withErrors([
                'roles' => 'You cannot remove your own Super Admin role from this screen.',
            ]);
        }

        if ($request->user()?->is($user)
            && $request->user()?->hasRole(SystemRole::ADMIN)
            && ! in_array(SystemRole::ADMIN, $roles, true)
        ) {
            return to_route('users.edit', $user)->withErrors([
                'roles' => 'You cannot remove your own Admin role from this screen.',
            ]);
        }

        if (! $request->user()?->hasRole(SystemRole::SUPER_ADMIN) && $user->hasRole(SystemRole::SUPER_ADMIN)) {
            return to_route('users.edit', $user)->withErrors([
                'roles' => 'Only a Super Admin can modify roles on a Super Admin account.',
            ]);
        }

        $user->syncRoles($roles);
        $user->notify(new SystemMessageNotification(
            title: 'Your access changed',
            message: 'An administrator updated your assigned roles. Refresh your session if your navigation changed.',
            actionUrl: '/dashboard',
            actionLabel: 'Review workspace',
        ));

        ActivityLogger::record(
            actor: $request->user(),
            event: 'users.roles-updated',
            description: "Updated roles for {$user->email}.",
            subject: $user,
            properties: [
                'roles' => $roles,
            ],
            request: $request,
        );

        return to_route('users.edit', $user)->with('success', 'User roles updated successfully.');
    }

    /**
     * Delete the selected user.
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->hasBusinessIdentity() || $user->isProtectedDemoPersona()) {
            ActivityLogger::record(
                actor: $request->user(),
                event: 'users.business-delete-denied',
                description: "Denied technical deletion of protected/business identity {$user->email}.",
                subject: $user,
                request: $request,
            );

            abort(403, 'Sunday School business identities and protected demo personas cannot be deleted by a technical operator.');
        }

        $this->authorize('delete', $user);

        if ($request->user()?->is($user)) {
            return to_route('users.index')->with('error', 'You cannot delete the currently signed-in user.');
        }

        ActivityLogger::record(
            actor: $request->user(),
            event: 'users.deleted',
            description: "Deleted user {$user->email}.",
            subject: $user,
            request: $request,
        );

        $user->delete();

        return to_route('users.index')->with('success', 'User deleted successfully.');
    }

    /**
     * Normalize a user for admin page props.
     *
     * @return array{id: int, name: string, email: string, roles: array<int, string>, isCurrentUser: bool, isBusinessIdentity: bool, canEdit: bool, canDelete: bool, emailVerifiedAt: string|null, createdAt: string|null}
     */
    private function userSummary(User $user, Request $request): array
    {
        $actor = $request->user();
        $isBusiness = $user->hasBusinessIdentity();

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'roles' => $user->roles->pluck('name')->values()->all(),
            'isCurrentUser' => $actor?->is($user) ?? false,
            'isBusinessIdentity' => $isBusiness,
            'canEdit' => $actor !== null ? $actor->can('update', $user) : false,
            'canDelete' => $actor !== null ? $actor->can('delete', $user) : false,
            'emailVerifiedAt' => $user->email_verified_at?->toDateTimeString(),
            'createdAt' => $user->created_at?->toDateTimeString(),
        ];
    }

    /**
     * Get role options for form checkboxes.
     *
     * @return array<int, array{name: string, label: string, description: string|null, usersCount: int}>
     */
    private function roleOptions(Request $request): array
    {
        $assignable = SystemRole::assignableRoleNamesFor($request->user());

        return Role::query()
            ->whereIn('name', $assignable)
            ->withCount('users')
            ->orderBy('name')
            ->get()
            ->map(fn (Role $role): array => [
                'name' => $role->name,
                'label' => $role->name,
                'description' => $role->description,
                'usersCount' => $role->users_count,
            ])
            ->values()
            ->all();
    }
}
