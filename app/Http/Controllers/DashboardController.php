<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\DemoReport;
use App\Models\Media;
use App\Models\Organization;
use App\Models\SchoolMembership;
use App\Models\User;
use App\Support\HierarchyScopeContext;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $actor = $request->user();
        $scope = HierarchyScopeContext::resolveForRequest($request);

        $activityQuery = ActivityLog::query()->excludePrivateSchoolEvents();

        $metrics = collect([
            $actor->can('users.view') ? ['key' => 'users', 'label' => __('messages.nav.users'), 'value' => User::query()->count(), 'description' => __('messages.demo.dashboard.metric_users_desc'), 'tone' => 'sky', 'href' => route('users.index')] : null,
            $actor->can('roles.view') ? ['key' => 'roles', 'label' => __('messages.nav.roles'), 'value' => Role::query()->count(), 'description' => __('messages.demo.dashboard.metric_roles_desc'), 'tone' => 'violet', 'href' => route('roles.index')] : null,
            $actor->can('media.view') ? ['key' => 'media', 'label' => __('messages.nav.media'), 'value' => Media::query()->count(), 'description' => __('messages.demo.dashboard.metric_media_desc'), 'tone' => 'emerald', 'href' => route('media.index')] : null,
            $actor->can('activity-logs.view') ? ['key' => 'activity', 'label' => __('messages.nav.activity_logs'), 'value' => (clone $activityQuery)->count(), 'description' => __('messages.demo.dashboard.metric_activity_desc'), 'tone' => 'amber', 'href' => route('activity-logs.index')] : null,
        ])->filter()->values()->all();

        return Inertia::render('Dashboard', [
            'metrics' => $metrics,
            'queues' => [],
            'recentActivity' => $actor->can('activity-logs.view')
                ? $activityQuery->latest('created_at')->limit(6)->get()->map(fn (ActivityLog $log): array => ['id' => $log->id, 'event' => $log->event, 'description' => $log->description, 'createdAt' => $log->created_at?->toDateTimeString()])->all()
                : [],
            'scopeSummary' => $scope->toInertiaPayload(),
            'demoOverview' => $this->buildDemoOverview($scope),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildDemoOverview(HierarchyScopeContext $scope): array
    {
        $roleKey = $scope->roleKey();
        $org = $scope->appointment?->organization ?? $scope->membership?->organization;
        $unit = $scope->appointment?->organizationalUnit;

        $visibleReportsCount = 0;
        if ($scope->canViewReportsMenu()) {
            $visibleReportsCount = DemoReport::query()
                ->with('organization')
                ->get()
                ->filter(fn (DemoReport $r): bool => $scope->canViewReport($r))
                ->count();
        }

        $pendingMembersCount = 0;
        if ($scope->isSchoolLead() && $org !== null) {
            $pendingMembersCount = SchoolMembership::query()
                ->where('organization_id', $org->id)
                ->where(function ($query): void {
                    $query
                        ->where('status', SchoolMembership::STATUS_PENDING)
                        ->orWhere('account_link_status', SchoolMembership::LINK_PENDING_LEAD_APPROVAL);
                })
                ->count();
        }

        $jurisdictionSchools = [];
        if ($org !== null && $scope->canViewHierarchyExplorer()) {
            $subtreeIds = $org->subtreeIds();
            $jurisdictionSchools = Organization::query()
                ->where('type', Organization::TYPE_SUNDAY_SCHOOL)
                ->whereIn('id', $subtreeIds)
                ->orderBy('id')
                ->get()
                ->filter(fn (Organization $school): bool => $scope->canViewSchoolWorkspace($school))
                ->map(fn (Organization $school): array => [
                    'id' => $school->id,
                    'nameAm' => $school->name_am,
                    'nameEn' => $school->name_en,
                    'parishNameAm' => $school->parish_name_am,
                    'parishNameEn' => $school->parish_name_en,
                    'cityAm' => $school->city_am,
                    'cityEn' => $school->city_en,
                    'href' => route('schools.show', $school),
                ])
                ->values()
                ->all();
        }

        $nationalSummary = null;
        if ($scope->hasCapability('reports.view_national_summary')) {
            $schools = Organization::query()
                ->where('type', Organization::TYPE_SUNDAY_SCHOOL)
                ->withCount([
                    'demoReports as released_reports_count' => fn ($q) => $q->where('status', DemoReport::STATUS_RELEASED),
                ])
                ->get();

            $nationalSummary = [
                'diocesesCount' => Organization::query()->where('type', Organization::TYPE_DIOCESE)->count(),
                'districtsCount' => Organization::query()->where('type', Organization::TYPE_DISTRICT)->count(),
                'schoolsCount' => $schools->count(),
                'approvedMembersTotal' => SchoolMembership::query()
                    ->where('status', SchoolMembership::STATUS_APPROVED)
                    ->count(),
                'releasedReportsTotal' => DemoReport::query()
                    ->where('status', DemoReport::STATUS_RELEASED)
                    ->count(),
                'reportingSchoolsCount' => $schools->where('released_reports_count', '>', 0)->count(),
                'missingSchoolsCount' => $schools->where('released_reports_count', 0)->count(),
            ];
        }

        return [
            'roleKey' => $roleKey,
            'organization' => $org === null ? null : [
                'id' => $org->id,
                'code' => $org->code,
                'type' => $org->type,
                'nameAm' => $org->name_am,
                'nameEn' => $org->name_en,
                'cityAm' => $org->city_am,
                'cityEn' => $org->city_en,
                'parishNameAm' => $org->parish_name_am,
                'parishNameEn' => $org->parish_name_en,
                'schoolHref' => $org->type === Organization::TYPE_SUNDAY_SCHOOL && $scope->canViewSchoolWorkspace($org)
                    ? route('schools.show', $org)
                    : null,
            ],
            'unit' => $unit === null ? null : [
                'id' => $unit->id,
                'code' => $unit->code,
                'unitKind' => $unit->unit_kind,
                'nameAm' => $unit->name_am,
                'nameEn' => $unit->name_en,
                'departmentHref' => $scope->canViewDepartmentWorkspace($unit)
                    ? route('departments.show', $unit)
                    : null,
            ],
            'visibleReportsCount' => $visibleReportsCount,
            'pendingMembersCount' => $pendingMembersCount,
            'jurisdictionSchools' => $jurisdictionSchools,
            'nationalSummary' => $nationalSummary,
            'allowedSummaryAm' => $this->allowedSummaryForRole($roleKey),
            'deniedSummaryAm' => $this->deniedSummaryForRole($roleKey),
        ];
    }

    /**
     * @return list<string>
     */
    private function allowedSummaryForRole(string $roleKey): array
    {
        $normalizedKey = in_array($roleKey, ['unlinked', 'rejected_member', 'suspended_member', 'archived_member'], true)
            ? 'pending_member'
            : $roleKey;
        $translated = __("messages.demo.role_summaries.{$normalizedKey}.allowed");

        return is_array($translated) ? array_values($translated) : [];
    }

    /**
     * @return list<string>
     */
    private function deniedSummaryForRole(string $roleKey): array
    {
        $normalizedKey = in_array($roleKey, ['unlinked', 'rejected_member', 'suspended_member', 'archived_member'], true)
            ? 'pending_member'
            : $roleKey;
        $translated = __("messages.demo.role_summaries.{$normalizedKey}.denied");

        return is_array($translated) ? array_values($translated) : [];
    }
}
