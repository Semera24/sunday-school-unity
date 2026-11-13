<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Media;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $actor = $request->user();
        $metrics = collect([
            $actor->can('users.view') ? ['key' => 'users', 'label' => 'Users', 'value' => User::query()->count(), 'description' => 'Registered workspace accounts.', 'tone' => 'sky', 'href' => route('users.index')] : null,
            $actor->can('roles.view') ? ['key' => 'roles', 'label' => 'Roles', 'value' => Role::query()->count(), 'description' => 'Configured access bundles.', 'tone' => 'violet', 'href' => route('roles.index')] : null,
            $actor->can('media.view') ? ['key' => 'media', 'label' => 'Media files', 'value' => Media::query()->count(), 'description' => 'Files in the shared media library.', 'tone' => 'emerald', 'href' => route('media.index')] : null,
            $actor->can('activity-logs.view') ? ['key' => 'activity', 'label' => 'Audit events', 'value' => ActivityLog::query()->count(), 'description' => 'Recorded workspace activity.', 'tone' => 'amber', 'href' => route('activity-logs.index')] : null,
        ])->filter()->values()->all();

        return Inertia::render('Dashboard', [
            'metrics' => $metrics,
            'queues' => [],
            'recentActivity' => $actor->can('activity-logs.view')
                ? ActivityLog::query()->latest('created_at')->limit(6)->get()->map(fn (ActivityLog $log): array => ['id' => $log->id, 'event' => $log->event, 'description' => $log->description, 'createdAt' => $log->created_at?->toDateTimeString()])->all()
                : [],
        ]);
    }
}
