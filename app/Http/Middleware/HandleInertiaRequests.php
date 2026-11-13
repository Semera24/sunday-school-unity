<?php

namespace App\Http\Middleware;

use App\Support\Locales;
use App\Support\SettingStore;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function share(Request $request): array
    {
        $user = $request->user();
        $sharedSettings = SettingStore::shared();

        return [...parent::share($request), 'name' => $sharedSettings['appDisplayName'], 'settings' => $sharedSettings, 'auth' => ['user' => $user, 'actorLabel' => $user?->getRoleNames()->join(', ') ?: 'Signed-in account', 'roles' => $user?->getRoleNames()->values()->all() ?? [], 'permissions' => $user?->getAllPermissions()->pluck('name')->values()->all() ?? [], 'can' => ['viewDashboard' => $user?->can('dashboard.view') ?? false, 'viewSearch' => $user?->can('search.view') ?? false, 'viewExports' => $user?->can('exports.view') ?? false, 'manageSettings' => $user?->can('settings.view') ?? false, 'manageUsers' => $user?->can('users.view') ?? false, 'manageRoles' => $user?->can('roles.view') ?? false, 'viewNotifications' => $user?->can('notifications.view') ?? false, 'viewActivityLogs' => $user?->can('activity-logs.view') ?? false], 'notificationCount' => $user?->unreadNotifications()->count() ?? 0, 'notificationPreview' => $user?->notifications()->latest()->limit(5)->get()->map(fn (DatabaseNotification $notification): array => ['id' => $notification->id, 'title' => $notification->data['title'] ?? 'Notification', 'message' => $notification->data['message'] ?? '', 'actionUrl' => $notification->data['action_url'] ?? null, 'actionLabel' => $notification->data['action_label'] ?? null, 'level' => $notification->data['level'] ?? 'info', 'readAt' => $notification->read_at?->toISOString(), 'createdAt' => $notification->created_at?->toISOString()])->values()->all() ?? []], 'flash' => ['success' => fn (): ?string => $request->session()->get('success'), 'error' => fn (): ?string => $request->session()->get('error')], 'locale' => app()->getLocale(), 'availableLocales' => Locales::all(), 'translations' => __('messages'), 'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true'];
    }
}
