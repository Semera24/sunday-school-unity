<?php

namespace App\Http\Controllers;

use App\Mail\SchoolMembershipInvitationMail;
use App\Models\DemoReport;
use App\Models\DepartmentMembership;
use App\Models\MembershipDecision;
use App\Models\MembershipInvitation;
use App\Models\Organization;
use App\Models\OrganizationalUnit;
use App\Models\Person;
use App\Models\SchoolMembership;
use App\Models\ScopedAppointment;
use App\Models\User;
use App\Support\ActivityLogger;
use App\Support\HierarchyScopeContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class HierarchyDemoController extends Controller
{
    public function switchScope(Request $request): RedirectResponse
    {
        $scope = HierarchyScopeContext::resolveForRequest($request);

        $validated = $request->validate([
            'scope_key' => ['required', 'string'],
        ]);

        $target = $scope->availableContexts->firstWhere('key', $validated['scope_key']);

        abort_if($target === null, 403, 'You are not authorized to switch into the requested organizational scope.');

        $request->session()->put(HierarchyScopeContext::SESSION_KEY, $target['key']);

        ActivityLogger::record(
            actor: $request->user(),
            event: 'workspace.scope-switched',
            description: "Switched active scope to {$target['key']} ({$target['titleEn']}).",
            properties: [
                'scope_key' => $target['key'],
                'role_key' => $target['roleKey'],
                'organization_id' => $target['organizationId'],
                'unit_id' => $target['unitId'],
            ],
            request: $request,
        );

        return to_route('dashboard')->with('success', __('messages.demo.flash.scope_switched', [
            'title' => app()->getLocale() === 'en' ? $target['titleEn'] : $target['titleAm'],
        ]));
    }

    public function hierarchyIndex(Request $request): Response
    {
        $scope = HierarchyScopeContext::resolveForRequest($request);
        abort_unless($scope->canViewHierarchyExplorer(), 403, 'Your current scope cannot access the organizational hierarchy directory.');

        $organizations = Organization::query()
            ->with(['units', 'parent'])
            ->withCount([
                'schoolMemberships as approved_members_count' => fn ($q) => $q->where('status', SchoolMembership::STATUS_APPROVED),
                'demoReports as released_reports_count' => fn ($q) => $q->where('status', DemoReport::STATUS_RELEASED),
            ])
            ->orderBy('id')
            ->get();

        $activeOrg = $scope->appointment?->organization;

        $nodes = $organizations->map(function (Organization $org) use ($scope, $activeOrg): array {
            $inJurisdiction = $scope->isTechnicalOperator()
                || ($activeOrg !== null && $org->isWithinJurisdictionOf($activeOrg));

            return [
                'id' => $org->id,
                'parentId' => $org->parent_id,
                'code' => $org->code,
                'type' => $org->type,
                'status' => $org->status,
                'nameAm' => $org->name_am,
                'nameEn' => $org->name_en,
                'cityAm' => $org->city_am,
                'cityEn' => $org->city_en,
                'parishNameAm' => $org->parish_name_am,
                'parishNameEn' => $org->parish_name_en,
                'inJurisdiction' => $inJurisdiction,
                'canOpenSchoolWorkspace' => $org->type === Organization::TYPE_SUNDAY_SCHOOL && $scope->canViewSchoolWorkspace($org),
                'canManageLocally' => $org->type === Organization::TYPE_SUNDAY_SCHOOL && $scope->canManageSchoolLocally($org),
                'canRegisterSubordinate' => $scope->canRegisterSubordinateOrganization($org),
                'canUpdateOrganization' => $scope->canUpdateOrganization($org),
                'canManageLocalLeads' => $scope->canManageLocalLeadAppointments($org),
                'approvedMembersCount' => $inJurisdiction ? $org->approved_members_count : null,
                'releasedReportsCount' => $inJurisdiction ? $org->released_reports_count : null,
                'units' => $org->units->map(fn (OrganizationalUnit $unit): array => [
                    'id' => $unit->id,
                    'code' => $unit->code,
                    'unitKind' => $unit->unit_kind,
                    'categoryCode' => $unit->category_code,
                    'nameAm' => $unit->name_am,
                    'nameEn' => $unit->name_en,
                    'canOpenDepartment' => $scope->canViewDepartmentWorkspace($unit),
                ])->values()->all(),
            ];
        })->values()->all();

        $localLeadAppointments = [];
        $appointableUsers = [];
        if ($scope->isUnionSuperAdmin()) {
            $localLeadAppointments = ScopedAppointment::query()
                ->where('role_key', ScopedAppointment::ROLE_SCHOOL_LEAD)
                ->with(['user:id,name,email', 'organization:id,code,name_am,name_en'])
                ->orderBy('organization_id')
                ->orderBy('id')
                ->get()
                ->map(fn (ScopedAppointment $appt): array => [
                    'id' => $appt->id,
                    'userId' => $appt->user_id,
                    'userName' => $appt->user?->name ?? '',
                    'userEmail' => $appt->user?->email ?? '',
                    'organizationId' => $appt->organization_id,
                    'organizationNameAm' => $appt->organization?->name_am ?? '',
                    'organizationNameEn' => $appt->organization?->name_en,
                    'titleAm' => $appt->title_am,
                    'titleEn' => $appt->title_en,
                    'startsOn' => $appt->starts_on?->toDateString(),
                    'endsOn' => $appt->ends_on?->toDateString(),
                    'isActive' => $appt->is_active,
                    'canUpdate' => $scope->canUpdateAppointment($appt),
                ])
                ->values()
                ->all();

            $appointableUsers = User::query()
                ->whereDoesntHave('roles')
                ->whereDoesntHave(
                    'scopedAppointments',
                    fn ($q) => $q->where('role_key', ScopedAppointment::ROLE_TECHNICAL_OPERATOR)
                )
                ->orderBy('id')
                ->get(['id', 'name', 'email'])
                ->map(fn (User $u): array => [
                    'id' => $u->id,
                    'name' => $u->name,
                    'email' => $u->email,
                ])
                ->values()
                ->all();
        }

        return Inertia::render('hierarchy/Index', [
            'organizations' => $nodes,
            'localLeadAppointments' => $localLeadAppointments,
            'appointableUsers' => $appointableUsers,
            'scopeSummary' => $scope->toInertiaPayload(),
        ]);
    }

    public function storeOrganization(Request $request): RedirectResponse
    {
        $scope = HierarchyScopeContext::resolveForRequest($request);

        $validated = $request->validate([
            'parent_id' => ['required', 'integer', 'exists:organizations,id'],
            'type' => [
                'required',
                'string',
                Rule::in([
                    Organization::TYPE_DIOCESE,
                    Organization::TYPE_DISTRICT,
                    Organization::TYPE_SUNDAY_SCHOOL,
                ]),
            ],
            'code' => ['required', 'string', 'max:80', 'alpha_dash', 'unique:organizations,code'],
            'name_am' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'city_am' => ['nullable', 'string', 'max:255'],
            'city_en' => ['nullable', 'string', 'max:255'],
            'parish_name_am' => ['nullable', 'string', 'max:255'],
            'parish_name_en' => ['nullable', 'string', 'max:255'],
        ]);

        $parent = Organization::query()->findOrFail($validated['parent_id']);
        abort_unless(
            $scope->canCreateOrganizationUnder($parent, $validated['type']),
            403,
            'Your active scope is not authorized to register this organization type under the selected parent.'
        );

        $organization = Organization::query()->create([
            ...$validated,
            'status' => 'active',
        ]);

        ActivityLogger::record(
            actor: $request->user(),
            event: 'organizations.created',
            description: "Registered organization {$organization->code} ({$organization->type}) under {$parent->code}.",
            subject: $organization,
            properties: [
                'organization_id' => $organization->id,
                'code' => $organization->code,
                'type' => $organization->type,
                'parent_id' => $parent->id,
                'actor_role_key' => $scope->roleKey(),
            ],
            request: $request,
        );

        return to_route('hierarchy.index')->with('success', __('messages.demo.flash.organization_created', [
            'name' => $organization->name_am,
        ]));
    }

    public function updateOrganization(Request $request, Organization $organization): RedirectResponse
    {
        $scope = HierarchyScopeContext::resolveForRequest($request);
        abort_unless(
            $scope->canUpdateOrganization($organization),
            403,
            'Only the Union Super Administrator is authorized to update organization records nationwide.'
        );

        $validated = $request->validate([
            'name_am' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'city_am' => ['nullable', 'string', 'max:255'],
            'city_en' => ['nullable', 'string', 'max:255'],
            'parish_name_am' => ['nullable', 'string', 'max:255'],
            'parish_name_en' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'string', Rule::in(['active', 'inactive'])],
        ]);

        if ($organization->type === Organization::TYPE_UNION && $validated['status'] !== 'active') {
            throw ValidationException::withMessages([
                'status' => ['The root Central Union organization cannot be marked inactive.'],
            ]);
        }

        $before = $organization->only([
            'name_am',
            'name_en',
            'city_am',
            'city_en',
            'parish_name_am',
            'parish_name_en',
            'status',
        ]);

        $organization->update($validated);

        ActivityLogger::record(
            actor: $request->user(),
            event: 'organizations.updated',
            description: "Updated organization {$organization->code} (#{$organization->id}).",
            subject: $organization,
            properties: [
                'before' => $before,
                'after' => $organization->only(array_keys($before)),
                'actor_role_key' => $scope->roleKey(),
            ],
            request: $request,
        );

        return to_route('hierarchy.index')->with('success', __('messages.demo.flash.organization_updated', [
            'name' => $organization->name_am,
        ]));
    }

    public function storeLocalLeadAppointment(Request $request): RedirectResponse
    {
        $scope = HierarchyScopeContext::resolveForRequest($request);
        abort_unless(
            $scope->isUnionSuperAdmin(),
            403,
            'Only the Union Super Administrator may appoint or update local Sunday School leads.'
        );

        if ($request->filled('role_key') && $request->input('role_key') !== ScopedAppointment::ROLE_SCHOOL_LEAD) {
            abort(403, 'Union Super Administrator appointment management on this endpoint is strictly bounded to school_lead.');
        }

        $validated = $request->validate([
            'organization_id' => ['required', 'integer', 'exists:organizations,id'],
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'title_am' => ['required', 'string', 'max:255'],
            'title_en' => ['nullable', 'string', 'max:255'],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $school = Organization::query()->findOrFail($validated['organization_id']);
        abort_unless(
            $scope->canManageLocalLeadAppointments($school),
            403,
            'Local lead appointments may only be granted on a Sunday School within the Union hierarchy.'
        );

        $targetUser = User::query()->findOrFail($validated['user_id']);
        if ($targetUser->hasTechnicalIdentity()) {
            ActivityLogger::record(
                actor: $request->user(),
                event: 'appointments.technical-incompatibility-denied',
                description: "Denied school_lead appointment for technical identity {$targetUser->email} (#{$targetUser->id}).",
                subject: $targetUser,
                properties: [
                    'target_user_id' => $targetUser->id,
                    'target_user_email' => $targetUser->email,
                    'organization_id' => $school->id,
                    'attempted_role_key' => ScopedAppointment::ROLE_SCHOOL_LEAD,
                ],
                request: $request,
            );

            throw ValidationException::withMessages([
                'user_id' => ['Technical operator accounts cannot be granted Sunday School lead business appointments.'],
            ]);
        }

        $appointment = ScopedAppointment::query()->updateOrCreate(
            [
                'user_id' => $targetUser->id,
                'organization_id' => $school->id,
                'role_key' => ScopedAppointment::ROLE_SCHOOL_LEAD,
            ],
            [
                'organizational_unit_id' => null,
                'title_am' => $validated['title_am'],
                'title_en' => $validated['title_en'] ?? null,
                'starts_on' => $validated['starts_on'],
                'ends_on' => $validated['ends_on'] ?? null,
                'is_active' => (bool) ($validated['is_active'] ?? true),
            ]
        );

        ActivityLogger::record(
            actor: $request->user(),
            event: 'appointments.local-lead-assigned',
            description: "Assigned school_lead appointment #{$appointment->id} to {$targetUser->email} for school {$school->code}.",
            subject: $appointment,
            properties: [
                'appointment_id' => $appointment->id,
                'target_user_id' => $targetUser->id,
                'target_user_email' => $targetUser->email,
                'organization_id' => $school->id,
                'organization_code' => $school->code,
                'role_key' => $appointment->role_key,
                'starts_on' => $appointment->starts_on?->toDateString(),
                'ends_on' => $appointment->ends_on?->toDateString(),
                'is_active' => $appointment->is_active,
            ],
            request: $request,
        );

        return to_route('hierarchy.index')->with('success', __('messages.demo.flash.local_lead_assigned'));
    }

    public function showSchool(Request $request, Organization $organization): Response
    {
        $scope = HierarchyScopeContext::resolveForRequest($request);
        abort_unless($scope->canViewSchoolWorkspace($organization), 403, 'Access denied: this Sunday School workspace is outside your active organizational scope.');

        $organization->load(['parent.parent', 'units']);

        $canManageLocally = $scope->canManageSchoolLocally($organization);
        $canViewDirectory = $scope->canViewSchoolMembersDirectory($organization);

        $search = $request->string('search')->trim()->toString();
        $statusFilter = $request->string('status')->trim()->toString();
        if ($statusFilter === '' || ! in_array($statusFilter, ['all', ...SchoolMembership::ALL_STATUSES], true)) {
            $statusFilter = 'all';
        }
        $departmentFilter = $request->integer('department_id');

        $memberships = [];
        $membershipPagination = null;
        $statusCounts = [
            'all' => 0,
            'approved' => 0,
            'pending' => 0,
            'rejected' => 0,
            'suspended' => 0,
            'archived' => 0,
            'pendingLinks' => 0,
        ];
        $leadReviewQueue = [
            'pendingMemberships' => [],
            'pendingLinkInvitations' => [],
            'activeInvitations' => [],
        ];
        $localDuplicateCandidates = [];

        if ($canViewDirectory) {
            $baseSchoolQuery = SchoolMembership::query()->where('organization_id', $organization->id);

            $groupedStatuses = (clone $baseSchoolQuery)
                ->selectRaw('status, COUNT(*) as aggregate')
                ->groupBy('status')
                ->pluck('aggregate', 'status');

            $statusCounts = [
                'all' => (int) $groupedStatuses->sum(),
                'approved' => (int) ($groupedStatuses[SchoolMembership::STATUS_APPROVED] ?? 0),
                'pending' => (int) ($groupedStatuses[SchoolMembership::STATUS_PENDING] ?? 0),
                'rejected' => (int) ($groupedStatuses[SchoolMembership::STATUS_REJECTED] ?? 0),
                'suspended' => (int) ($groupedStatuses[SchoolMembership::STATUS_SUSPENDED] ?? 0),
                'archived' => (int) ($groupedStatuses[SchoolMembership::STATUS_ARCHIVED] ?? 0),
                'pendingLinks' => (int) (clone $baseSchoolQuery)
                    ->where('account_link_status', SchoolMembership::LINK_PENDING_LEAD_APPROVAL)
                    ->count(),
            ];

            $directoryQuery = SchoolMembership::query()
                ->where('organization_id', $organization->id)
                ->with([
                    'person',
                    'user:id,name,email',
                    'pendingUser:id,name,email',
                    'approvedBy:id,name',
                    'departmentMemberships.organizationalUnit',
                ]);

            if ($statusFilter !== 'all') {
                $directoryQuery->where('status', $statusFilter);
            }

            if ($departmentFilter > 0) {
                $directoryQuery->whereHas('departmentMemberships', function (Builder $q) use ($departmentFilter): void {
                    $q->where('organizational_unit_id', $departmentFilter)
                        ->where('is_active', true);
                });
            }

            if ($search !== '') {
                $pattern = '%'.mb_strtolower($search).'%';
                $directoryQuery->where(function (Builder $q) use ($pattern): void {
                    $q->whereRaw('LOWER(COALESCE(membership_code, \'\')) LIKE ?', [$pattern])
                        ->orWhereHas('person', function (Builder $personQuery) use ($pattern): void {
                            $personQuery
                                ->whereRaw('LOWER(COALESCE(full_name, \'\')) LIKE ?', [$pattern])
                                ->orWhereRaw('LOWER(COALESCE(name_en, \'\')) LIKE ?', [$pattern])
                                ->orWhereRaw('LOWER(COALESCE(phone, \'\')) LIKE ?', [$pattern]);
                        })
                        ->orWhereHas('user', function (Builder $userQuery) use ($pattern): void {
                            $userQuery->whereRaw('LOWER(COALESCE(email, \'\')) LIKE ?', [$pattern]);
                        })
                        ->orWhereHas('pendingUser', function (Builder $pendingQuery) use ($pattern): void {
                            $pendingQuery->whereRaw('LOWER(COALESCE(email, \'\')) LIKE ?', [$pattern]);
                        });
                });
            }

            $paginated = $directoryQuery
                ->orderByRaw("CASE WHEN status = 'pending' OR account_link_status = 'pending_lead_approval' THEN 0 ELSE 1 END")
                ->orderByDesc('id')
                ->paginate(10)
                ->withQueryString()
                ->through(fn (SchoolMembership $m): array => [
                    'id' => $m->id,
                    'membershipCode' => $m->membership_code,
                    'status' => $m->status,
                    'accountLinkStatus' => $m->account_link_status,
                    'statusReason' => $m->status_reason,
                    'fullName' => $m->person?->full_name ?? '',
                    'nameEn' => $m->person?->name_en,
                    'phone' => $m->person?->phone,
                    'accountEmail' => $m->user?->email,
                    'pendingAccountEmail' => $m->pendingUser?->email,
                    'applicationNote' => $m->application_note,
                    'approvedBy' => $m->approvedBy?->name,
                    'approvedAt' => $m->approved_at?->toDateTimeString(),
                    'joinedOn' => $m->joined_on?->toDateString(),
                    'departments' => $m->departmentMemberships
                        ->filter(fn (DepartmentMembership $dm): bool => $dm->isCurrentlyEffective())
                        ->map(fn ($dm): string => app()->getLocale() === 'en'
                            ? ($dm->organizationalUnit?->name_en ?: ($dm->organizationalUnit?->name_am ?? ''))
                            : ($dm->organizationalUnit?->name_am ?? ''))
                        ->filter()
                        ->values()
                        ->all(),
                    'canApprove' => $m->status === SchoolMembership::STATUS_PENDING && $scope->canApproveMembership($m),
                ]);

            $memberships = $paginated->items();
            $membershipPagination = [
                'currentPage' => $paginated->currentPage(),
                'lastPage' => $paginated->lastPage(),
                'perPage' => $paginated->perPage(),
                'total' => $paginated->total(),
                'prevPageUrl' => $paginated->previousPageUrl(),
                'nextPageUrl' => $paginated->nextPageUrl(),
            ];

            $pendingMemberships = SchoolMembership::query()
                ->where('organization_id', $organization->id)
                ->where('status', SchoolMembership::STATUS_PENDING)
                ->with(['person', 'user:id,name,email'])
                ->orderBy('id')
                ->get()
                ->map(fn (SchoolMembership $m): array => [
                    'id' => $m->id,
                    'membershipCode' => $m->membership_code,
                    'fullName' => $m->person?->full_name ?? '',
                    'nameEn' => $m->person?->name_en,
                    'phone' => $m->person?->phone,
                    'accountEmail' => $m->user?->email,
                    'applicationNote' => $m->application_note,
                    'createdAt' => $m->created_at?->toDateTimeString(),
                ])
                ->values()
                ->all();

            $pendingLinkInvitations = MembershipInvitation::query()
                ->where('organization_id', $organization->id)
                ->where('status', MembershipInvitation::STATUS_PENDING_LEAD_APPROVAL)
                ->with(['schoolMembership.person', 'acceptedBy:id,name,email'])
                ->orderBy('id')
                ->get()
                ->filter(function (MembershipInvitation $inv): bool {
                    $sm = $inv->schoolMembership;

                    return $sm !== null
                        && in_array($sm->status, [SchoolMembership::STATUS_APPROVED, SchoolMembership::STATUS_PENDING], true)
                        && $sm->user_id === null
                        && $sm->account_link_status === SchoolMembership::LINK_PENDING_LEAD_APPROVAL
                        && $sm->pending_user_id !== null
                        && (int) $sm->pending_user_id === (int) $inv->accepted_by_user_id;
                })
                ->map(fn (MembershipInvitation $inv): array => [
                    'id' => $inv->id,
                    'membershipId' => $inv->school_membership_id,
                    'memberFullName' => $inv->schoolMembership?->person?->full_name ?? $inv->invited_name ?? '',
                    'membershipCode' => $inv->schoolMembership?->membership_code,
                    'invitedEmail' => $inv->invited_email,
                    'acceptedByName' => $inv->acceptedBy?->name,
                    'acceptedByEmail' => $inv->acceptedBy?->email,
                    'acceptedAt' => $inv->accepted_at?->toDateTimeString(),
                ])
                ->values()
                ->all();

            $activeInvitations = MembershipInvitation::query()
                ->where('organization_id', $organization->id)
                ->whereIn('status', [
                    MembershipInvitation::STATUS_PENDING_ACCEPTANCE,
                    MembershipInvitation::STATUS_EXPIRED,
                ])
                ->with(['schoolMembership.person', 'createdBy:id,name'])
                ->orderByDesc('id')
                ->get()
                ->filter(function (MembershipInvitation $inv): bool {
                    $sm = $inv->schoolMembership;

                    return $sm !== null
                        && in_array($sm->status, [SchoolMembership::STATUS_APPROVED, SchoolMembership::STATUS_PENDING], true)
                        && $sm->user_id === null
                        && $sm->pending_user_id === null;
                })
                ->map(fn (MembershipInvitation $inv): array => [
                    'id' => $inv->id,
                    'membershipId' => $inv->school_membership_id,
                    'memberFullName' => $inv->schoolMembership?->person?->full_name ?? $inv->invited_name ?? '',
                    'invitedEmail' => $inv->invited_email,
                    'status' => $inv->effectiveStatus(),
                    'mailDeliveryStatus' => $inv->mail_delivery_status ?? MembershipInvitation::MAIL_PENDING,
                    'mailSentAt' => $inv->mail_sent_at?->toDateTimeString(),
                    'expiresAt' => $inv->expires_at?->toDateTimeString(),
                    'isExpired' => $inv->isExpired(),
                    'createdBy' => $inv->createdBy?->name,
                ])
                ->values()
                ->all();

            $leadReviewQueue = [
                'pendingMemberships' => $pendingMemberships,
                'pendingLinkInvitations' => $pendingLinkInvitations,
                'activeInvitations' => $activeInvitations,
            ];

            $localDuplicateCandidates = SchoolMembership::query()
                ->where('organization_id', $organization->id)
                ->with('person:id,full_name,name_en,phone')
                ->orderBy('id')
                ->get()
                ->map(fn (SchoolMembership $m): array => [
                    'membershipId' => $m->id,
                    'membershipCode' => $m->membership_code,
                    'status' => $m->status,
                    'fullName' => $m->person?->full_name ?? '',
                    'nameEn' => $m->person?->name_en,
                    'phone' => $m->person?->phone,
                ])
                ->values()
                ->all();
        }

        $coordinatorAppointments = [];
        $coordinatorCandidates = [];
        if ($scope->canManageDepartmentCoordinatorAppointments($organization)) {
            $coordinatorAppointments = ScopedAppointment::query()
                ->where('organization_id', $organization->id)
                ->where('role_key', ScopedAppointment::ROLE_DEPARTMENT_COORDINATOR)
                ->with(['user:id,name,email', 'organizationalUnit:id,code,name_am,name_en'])
                ->orderBy('id')
                ->get()
                ->map(fn (ScopedAppointment $appt): array => [
                    'id' => $appt->id,
                    'userId' => $appt->user_id,
                    'userName' => $appt->user?->name ?? '',
                    'userEmail' => $appt->user?->email ?? '',
                    'unitId' => $appt->organizational_unit_id,
                    'unitNameAm' => $appt->organizationalUnit?->name_am ?? '',
                    'unitNameEn' => $appt->organizationalUnit?->name_en,
                    'titleAm' => $appt->title_am,
                    'titleEn' => $appt->title_en,
                    'startsOn' => $appt->starts_on?->toDateString(),
                    'endsOn' => $appt->ends_on?->toDateString(),
                    'isActive' => $appt->is_active,
                    'canUpdate' => $scope->canUpdateAppointment($appt),
                ])
                ->values()
                ->all();

            $schoolUserIds = SchoolMembership::query()
                ->where('organization_id', $organization->id)
                ->where('status', SchoolMembership::STATUS_APPROVED)
                ->whereNotNull('user_id')
                ->pluck('user_id')
                ->unique()
                ->values();

            $coordinatorCandidates = User::query()
                ->whereIn('id', $schoolUserIds)
                ->whereDoesntHave('roles')
                ->whereDoesntHave(
                    'scopedAppointments',
                    fn ($q) => $q->where('role_key', ScopedAppointment::ROLE_TECHNICAL_OPERATOR)
                )
                ->orderBy('id')
                ->get(['id', 'name', 'email'])
                ->map(fn (User $u): array => [
                    'id' => $u->id,
                    'name' => $u->name,
                    'email' => $u->email,
                ])
                ->values()
                ->all();
        }

        $reports = DemoReport::query()
            ->where('organization_id', $organization->id)
            ->with(['organizationalUnit', 'submittedBy:id,name', 'approvedBy:id,name'])
            ->orderByDesc('id')
            ->get()
            ->filter(fn (DemoReport $report): bool => $scope->canViewReport($report))
            ->map(fn (DemoReport $report): array => $this->formatReportSummary($report, $scope))
            ->values()
            ->all();

        return Inertia::render('schools/Show', [
            'school' => [
                'id' => $organization->id,
                'code' => $organization->code,
                'nameAm' => $organization->name_am,
                'nameEn' => $organization->name_en,
                'cityAm' => $organization->city_am,
                'cityEn' => $organization->city_en,
                'parishNameAm' => $organization->parish_name_am,
                'parishNameEn' => $organization->parish_name_en,
                'districtNameAm' => $organization->parent?->name_am,
                'districtNameEn' => $organization->parent?->name_en,
                'dioceseNameAm' => $organization->parent?->parent?->name_am,
                'dioceseNameEn' => $organization->parent?->parent?->name_en,
                'canManageLocally' => $canManageLocally,
                'canViewDirectory' => $canViewDirectory,
            ],
            'departments' => $organization->units
                ->where('unit_kind', OrganizationalUnit::KIND_DEPARTMENT)
                ->map(fn (OrganizationalUnit $unit): array => [
                    'id' => $unit->id,
                    'code' => $unit->code,
                    'categoryCode' => $unit->category_code,
                    'nameAm' => $unit->name_am,
                    'nameEn' => $unit->name_en,
                    'canOpen' => $scope->canViewDepartmentWorkspace($unit),
                ])
                ->values()
                ->all(),
            'memberships' => $memberships,
            'membershipPagination' => $membershipPagination,
            'directoryFilters' => [
                'search' => $search,
                'status' => $statusFilter,
                'department_id' => $departmentFilter > 0 ? $departmentFilter : '',
            ],
            'statusCounts' => $statusCounts,
            'leadReviewQueue' => $leadReviewQueue,
            'localDuplicateCandidates' => $localDuplicateCandidates,
            'coordinatorAppointments' => $coordinatorAppointments,
            'coordinatorCandidates' => $coordinatorCandidates,
            'reports' => $reports,
            'scopeSummary' => $scope->toInertiaPayload(),
        ]);
    }

    public function storeMember(Request $request, Organization $organization): RedirectResponse
    {
        $scope = HierarchyScopeContext::resolveForRequest($request);
        abort_unless(
            $scope->canCreateMemberInSchool($organization),
            403,
            'Only the assigned local Sunday School lead can register members in this school.'
        );

        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:64'],
            'membership_code' => [
                'nullable',
                'string',
                'max:80',
                Rule::unique('school_memberships', 'membership_code')->where('organization_id', $organization->id),
            ],
            'joined_on' => ['nullable', 'date'],
            'service_notes' => ['nullable', 'string', 'max:1000'],
            'application_note' => ['nullable', 'string', 'max:1000'],
            'initial_status' => [
                'nullable',
                'string',
                Rule::in([SchoolMembership::STATUS_APPROVED, SchoolMembership::STATUS_PENDING]),
            ],
            'department_ids' => ['nullable', 'array'],
            'department_ids.*' => [
                'integer',
                Rule::exists('organizational_units', 'id')
                    ->where('organization_id', $organization->id)
                    ->where('unit_kind', OrganizationalUnit::KIND_DEPARTMENT),
            ],
            'department_role_label_am' => ['nullable', 'string', 'max:255'],
            'invite_email' => ['nullable', 'email', 'max:255'],
            'confirm_duplicate_override' => ['nullable', 'boolean'],
        ]);

        $normalizedName = mb_strtolower(trim($validated['full_name']));
        $normalizedPhone = isset($validated['phone']) ? trim((string) $validated['phone']) : '';

        $localDuplicateExists = SchoolMembership::query()
            ->where('organization_id', $organization->id)
            ->whereHas('person', function (Builder $personQuery) use ($normalizedName, $normalizedPhone): void {
                $personQuery->whereRaw('LOWER(TRIM(full_name)) = ?', [$normalizedName]);
                if ($normalizedPhone !== '') {
                    $personQuery->orWhere('phone', $normalizedPhone);
                }
            })
            ->exists();

        if ($localDuplicateExists && ! $request->boolean('confirm_duplicate_override')) {
            throw ValidationException::withMessages([
                'full_name' => [__('messages.demo.member_form.duplicate_warning_error')],
            ]);
        }

        if (! empty($validated['invite_email'])) {
            $invitedEmailLower = mb_strtolower(trim((string) $validated['invite_email']));
            $existingInvitedUser = User::query()->whereRaw('LOWER(email) = ?', [$invitedEmailLower])->first();
            if ($existingInvitedUser !== null) {
                if ($existingInvitedUser->hasTechnicalIdentity()) {
                    throw ValidationException::withMessages([
                        'invite_email' => [__('messages.demo.invitations.technical_incompatible')],
                    ]);
                }

                $alreadyInSameSchool = SchoolMembership::query()
                    ->where('organization_id', $organization->id)
                    ->where(function (Builder $q) use ($existingInvitedUser): void {
                        $q->where('user_id', $existingInvitedUser->id)
                            ->orWhere('pending_user_id', $existingInvitedUser->id);
                    })
                    ->exists();

                if ($alreadyInSameSchool) {
                    throw ValidationException::withMessages([
                        'invite_email' => [__('messages.demo.invitations.error_user_already_member_same_school')],
                    ]);
                }
            }
        }

        $actor = $request->user();
        $initialStatus = $validated['initial_status'] ?? SchoolMembership::STATUS_APPROVED;
        $isApproved = $initialStatus === SchoolMembership::STATUS_APPROVED;
        $joinedOn = $validated['joined_on'] ?? ($isApproved ? now()->toDateString() : null);

        /** @var array{membership: SchoolMembership, issued: array{invitation: MembershipInvitation, rawToken: string}|null} $txResult */
        $txResult = DB::transaction(function () use ($validated, $organization, $actor, $initialStatus, $isApproved, $joinedOn, $request): array {
            $person = Person::query()->create([
                'full_name' => $validated['full_name'],
                'name_en' => $validated['name_en'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'service_notes' => $validated['service_notes'] ?? null,
            ]);

            $membershipCode = ! empty($validated['membership_code'])
                ? $validated['membership_code']
                : sprintf('SSU-%d-%03d', $organization->id, SchoolMembership::query()->where('organization_id', $organization->id)->count() + 1);

            $membership = SchoolMembership::query()->create([
                'person_id' => $person->id,
                'organization_id' => $organization->id,
                'user_id' => null,
                'pending_user_id' => null,
                'membership_code' => $membershipCode,
                'status' => $initialStatus,
                'account_link_status' => SchoolMembership::LINK_NONE,
                'application_note' => $validated['application_note'] ?? null,
                'status_reason' => $isApproved
                    ? __('messages.demo.decisions.default_registered_approved_note')
                    : __('messages.demo.decisions.default_registered_pending_note'),
                'created_by_user_id' => $actor?->id,
                'approved_by_user_id' => $isApproved ? $actor?->id : null,
                'approved_at' => $isApproved ? now() : null,
                'joined_on' => $joinedOn,
            ]);

            $membership = SchoolMembership::query()
                ->with('person')
                ->whereKey($membership->id)
                ->lockForUpdate()
                ->firstOrFail();

            MembershipDecision::query()->create([
                'school_membership_id' => $membership->id,
                'organization_id' => $organization->id,
                'actor_user_id' => $actor?->id,
                'target_user_id' => null,
                'action' => $isApproved ? MembershipDecision::ACTION_APPROVED : MembershipDecision::ACTION_REGISTERED,
                'from_status' => null,
                'to_status' => $initialStatus,
                'reason' => $validated['application_note'] ?? ($isApproved
                    ? __('messages.demo.decisions.default_registered_approved_note')
                    : __('messages.demo.decisions.default_registered_pending_note')),
            ]);

            $deptIds = array_values(array_unique(array_map('intval', $validated['department_ids'] ?? [])));
            $roleLabel = ! empty($validated['department_role_label_am'])
                ? $validated['department_role_label_am']
                : 'አባል';

            try {
                foreach ($deptIds as $deptId) {
                    DepartmentMembership::query()->create([
                        'school_membership_id' => $membership->id,
                        'organizational_unit_id' => $deptId,
                        'role_label_am' => $roleLabel,
                        'starts_on' => $joinedOn ?? now()->toDateString(),
                        'ends_on' => null,
                        'is_active' => true,
                        'assigned_by_user_id' => $actor?->id,
                    ]);
                }
            } catch (UniqueConstraintViolationException) {
                throw ValidationException::withMessages([
                    'department_ids' => [__('messages.demo.department_membership.already_active')],
                ]);
            }

            ActivityLogger::record(
                actor: $actor,
                event: 'memberships.registered',
                description: "Registered member #{$membership->id} ({$membership->membership_code}) in school {$organization->code}.",
                subject: $membership,
                properties: [
                    'school_membership_id' => $membership->id,
                    'organization_id' => $organization->id,
                    'status' => $membership->status,
                    'department_ids' => $deptIds,
                ],
                request: $request,
            );

            $issued = null;
            if (! empty($validated['invite_email'])) {
                $issued = $this->issueInvitationForMembership(
                    school: $organization,
                    membership: $membership,
                    invitedEmail: (string) $validated['invite_email'],
                    actor: $actor,
                    request: $request,
                );
            }

            return [
                'membership' => $membership,
                'issued' => $issued,
            ];
        });

        $membership = $txResult['membership'];
        $issued = $txResult['issued'];

        if ($issued !== null) {
            $mailSent = $this->deliverCommittedInvitationMail(
                invitation: $issued['invitation'],
                school: $organization,
                membership: $membership,
                rawToken: $issued['rawToken'],
                actor: $actor,
                request: $request,
            );

            if (! $mailSent) {
                return to_route('members.show', $membership)->with('error', __('messages.demo.flash.member_registered_invite_mail_failed', [
                    'email' => $issued['invitation']->invited_email,
                ]));
            }

            return to_route('members.show', $membership)->with('success', __('messages.demo.flash.member_registered_and_invited', [
                'email' => $issued['invitation']->invited_email,
            ]));
        }

        return to_route('members.show', $membership)->with('success', __('messages.demo.flash.member_registered'));
    }

    public function updateMember(Request $request, SchoolMembership $schoolMembership): RedirectResponse
    {
        $scope = HierarchyScopeContext::resolveForRequest($request);
        abort_unless(
            $scope->canUpdateMemberRecord($schoolMembership),
            403,
            'Only the assigned local Sunday School lead can edit member records in this school.'
        );

        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:64'],
            'service_notes' => ['nullable', 'string', 'max:1000'],
            'membership_code' => [
                'nullable',
                'string',
                'max:80',
                Rule::unique('school_memberships', 'membership_code')
                    ->where('organization_id', $schoolMembership->organization_id)
                    ->ignore($schoolMembership->id),
            ],
            'joined_on' => ['nullable', 'date'],
        ]);

        DB::transaction(function () use ($request, $schoolMembership, $validated): void {
            $lockedMembership = SchoolMembership::query()
                ->whereKey($schoolMembership->id)
                ->lockForUpdate()
                ->firstOrFail();

            $person = Person::query()
                ->whereKey($lockedMembership->person_id)
                ->lockForUpdate()
                ->firstOrFail();

            $sharedWithOtherSchool = SchoolMembership::query()
                ->where('person_id', $person->id)
                ->where('organization_id', '!=', $lockedMembership->organization_id)
                ->lockForUpdate()
                ->exists();

            $personChanged = $person->full_name !== $validated['full_name']
                || ($person->name_en ?? null) !== ($validated['name_en'] ?? null)
                || ($person->phone ?? null) !== ($validated['phone'] ?? null)
                || ($person->service_notes ?? null) !== ($validated['service_notes'] ?? null);

            if ($sharedWithOtherSchool && $personChanged) {
                throw ValidationException::withMessages([
                    'full_name' => [__('messages.demo.member_form.shared_person_edit_deferred')],
                ]);
            }

            if ($personChanged) {
                $person->update([
                    'full_name' => $validated['full_name'],
                    'name_en' => $validated['name_en'] ?? null,
                    'phone' => $validated['phone'] ?? null,
                    'service_notes' => $validated['service_notes'] ?? null,
                ]);
            }

            $lockedMembership->update([
                'membership_code' => $validated['membership_code'] ?? $lockedMembership->membership_code,
                'joined_on' => $validated['joined_on'] ?? $lockedMembership->joined_on,
            ]);

            ActivityLogger::record(
                actor: $request->user(),
                event: 'memberships.updated',
                description: "Updated local member record #{$lockedMembership->id} in organization #{$lockedMembership->organization_id}.",
                subject: $lockedMembership,
                properties: [
                    'school_membership_id' => $lockedMembership->id,
                    'organization_id' => $lockedMembership->organization_id,
                ],
                request: $request,
            );
        });

        return back()->with('success', __('messages.demo.flash.member_updated'));
    }

    public function storeDepartment(Request $request, Organization $organization): RedirectResponse
    {
        $scope = HierarchyScopeContext::resolveForRequest($request);
        abort_unless($scope->canManageSchoolLocally($organization), 403, 'Only the assigned local Sunday School lead can create or configure departments for this school.');

        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:80',
                'alpha_dash',
                Rule::unique('organizational_units', 'code')->where('organization_id', $organization->id),
            ],
            'category_code' => ['required', 'string', 'max:64'],
            'name_am' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
        ]);

        $unit = OrganizationalUnit::query()->create([
            'organization_id' => $organization->id,
            'code' => $validated['code'],
            'unit_kind' => OrganizationalUnit::KIND_DEPARTMENT,
            'category_code' => $validated['category_code'],
            'name_am' => $validated['name_am'],
            'name_en' => $validated['name_en'] ?? null,
            'status' => 'active',
        ]);

        ActivityLogger::record(
            actor: $request->user(),
            event: 'departments.created',
            description: "Created department {$unit->code} in school {$organization->code}.",
            subject: $unit,
            request: $request,
        );

        return to_route('schools.show', $organization)->with('success', __('messages.demo.flash.department_created', [
            'name' => $unit->name_am,
        ]));
    }

    public function storeDepartmentCoordinatorAppointment(Request $request, Organization $organization): RedirectResponse
    {
        $scope = HierarchyScopeContext::resolveForRequest($request);
        abort_unless(
            $scope->canManageDepartmentCoordinatorAppointments($organization),
            403,
            'Only the assigned Sunday School lead for this school can appoint department coordinators.'
        );

        if ($request->filled('role_key') && $request->input('role_key') !== ScopedAppointment::ROLE_DEPARTMENT_COORDINATOR) {
            abort(403, 'A Sunday School lead may only grant department_coordinator appointments within their own school.');
        }

        $validated = $request->validate([
            'organizational_unit_id' => [
                'required',
                'integer',
                Rule::exists('organizational_units', 'id')
                    ->where('organization_id', $organization->id)
                    ->where('unit_kind', OrganizationalUnit::KIND_DEPARTMENT),
            ],
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'title_am' => ['required', 'string', 'max:255'],
            'title_en' => ['nullable', 'string', 'max:255'],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
        ]);

        $targetUser = User::query()->findOrFail($validated['user_id']);
        if ($targetUser->hasTechnicalIdentity()) {
            ActivityLogger::record(
                actor: $request->user(),
                event: 'appointments.technical-incompatibility-denied',
                description: "Denied department_coordinator appointment for technical identity {$targetUser->email} (#{$targetUser->id}).",
                subject: $targetUser,
                properties: [
                    'target_user_id' => $targetUser->id,
                    'target_user_email' => $targetUser->email,
                    'organization_id' => $organization->id,
                    'attempted_role_key' => ScopedAppointment::ROLE_DEPARTMENT_COORDINATOR,
                ],
                request: $request,
            );

            throw ValidationException::withMessages([
                'user_id' => ['Technical operator accounts cannot be granted local department coordinator appointments.'],
            ]);
        }

        $hasBlockedMembership = SchoolMembership::query()
            ->where('organization_id', $organization->id)
            ->where('user_id', $targetUser->id)
            ->whereIn('status', [
                SchoolMembership::STATUS_SUSPENDED,
                SchoolMembership::STATUS_ARCHIVED,
                SchoolMembership::STATUS_REJECTED,
                SchoolMembership::STATUS_PENDING,
            ])
            ->exists();

        if ($hasBlockedMembership) {
            throw ValidationException::withMessages([
                'user_id' => ['Cannot appoint a user whose membership in this Sunday School is pending, suspended, rejected, or archived.'],
            ]);
        }

        $belongsToSchool = SchoolMembership::query()
            ->where('organization_id', $organization->id)
            ->where('user_id', $targetUser->id)
            ->where('status', SchoolMembership::STATUS_APPROVED)
            ->exists()
            || ScopedAppointment::query()
                ->where('organization_id', $organization->id)
                ->where('user_id', $targetUser->id)
                ->exists();

        abort_unless(
            $belongsToSchool,
            403,
            'Cannot appoint a user who is not an approved member or existing officer of this Sunday School.'
        );

        $unit = OrganizationalUnit::query()->findOrFail($validated['organizational_unit_id']);

        $appointment = ScopedAppointment::query()->updateOrCreate(
            [
                'user_id' => $targetUser->id,
                'organization_id' => $organization->id,
                'organizational_unit_id' => $unit->id,
                'role_key' => ScopedAppointment::ROLE_DEPARTMENT_COORDINATOR,
            ],
            [
                'title_am' => $validated['title_am'],
                'title_en' => $validated['title_en'] ?? null,
                'starts_on' => $validated['starts_on'],
                'ends_on' => $validated['ends_on'] ?? null,
                'is_active' => true,
            ]
        );

        ActivityLogger::record(
            actor: $request->user(),
            event: 'appointments.coordinator-assigned',
            description: "Assigned department_coordinator appointment #{$appointment->id} to {$targetUser->email} in unit {$unit->code} ({$organization->code}).",
            subject: $appointment,
            properties: [
                'appointment_id' => $appointment->id,
                'target_user_id' => $targetUser->id,
                'target_user_email' => $targetUser->email,
                'organization_id' => $organization->id,
                'organizational_unit_id' => $unit->id,
                'role_key' => $appointment->role_key,
                'starts_on' => $appointment->starts_on?->toDateString(),
                'ends_on' => $appointment->ends_on?->toDateString(),
            ],
            request: $request,
        );

        return to_route('schools.show', $organization)->with('success', __('messages.demo.flash.coordinator_assigned'));
    }

    public function updateAppointment(Request $request, ScopedAppointment $scopedAppointment): RedirectResponse
    {
        $scope = HierarchyScopeContext::resolveForRequest($request);
        $scopedAppointment->load(['organization', 'organizationalUnit', 'user']);

        abort_unless(
            $scope->canUpdateAppointment($scopedAppointment),
            403,
            'Your current scope is not authorized to modify this appointment.'
        );

        if ($request->hasAny(['role_key', 'organization_id', 'organizational_unit_id', 'user_id'])) {
            abort(403, 'Appointment identity, role code, and organizational scope cannot be escalated via update.');
        }

        $validated = $request->validate([
            'title_am' => ['sometimes', 'required', 'string', 'max:255'],
            'title_en' => ['nullable', 'string', 'max:255'],
            'starts_on' => ['sometimes', 'required', 'date'],
            'ends_on' => ['nullable', 'date'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $startsOn = $validated['starts_on'] ?? $scopedAppointment->starts_on?->toDateString();
        $endsOn = array_key_exists('ends_on', $validated)
            ? $validated['ends_on']
            : $scopedAppointment->ends_on?->toDateString();

        if ($startsOn !== null && $endsOn !== null && strtotime((string) $endsOn) < strtotime((string) $startsOn)) {
            throw ValidationException::withMessages([
                'ends_on' => ['The appointment end date must be on or after the start date.'],
            ]);
        }

        $before = [
            'title_am' => $scopedAppointment->title_am,
            'title_en' => $scopedAppointment->title_en,
            'starts_on' => $scopedAppointment->starts_on?->toDateString(),
            'ends_on' => $scopedAppointment->ends_on?->toDateString(),
            'is_active' => $scopedAppointment->is_active,
        ];

        $scopedAppointment->update($validated);

        ActivityLogger::record(
            actor: $request->user(),
            event: 'appointments.updated',
            description: "Updated appointment #{$scopedAppointment->id} ({$scopedAppointment->role_key}) for user #{$scopedAppointment->user_id}.",
            subject: $scopedAppointment,
            properties: [
                'before' => $before,
                'after' => [
                    'title_am' => $scopedAppointment->title_am,
                    'title_en' => $scopedAppointment->title_en,
                    'starts_on' => $scopedAppointment->starts_on?->toDateString(),
                    'ends_on' => $scopedAppointment->ends_on?->toDateString(),
                    'is_active' => $scopedAppointment->is_active,
                ],
                'actor_role_key' => $scope->roleKey(),
            ],
            request: $request,
        );

        return back()->with('success', __('messages.demo.flash.appointment_updated'));
    }

    public function showDepartment(Request $request, OrganizationalUnit $organizationalUnit): Response
    {
        $scope = HierarchyScopeContext::resolveForRequest($request);
        abort_unless($scope->canViewDepartmentWorkspace($organizationalUnit), 403, 'Access denied: this department workspace is outside your assigned school/department scope.');

        $organizationalUnit->load(['organization.parent']);

        $members = $organizationalUnit->departmentMemberships()
            ->currentlyEffective()
            ->whereHas('schoolMembership', fn (Builder $q) => $q->where('status', SchoolMembership::STATUS_APPROVED))
            ->with(['schoolMembership.person', 'schoolMembership.departmentMemberships.organizationalUnit'])
            ->get()
            ->map(function (DepartmentMembership $dm): array {
                $sm = $dm->schoolMembership;

                return [
                    'id' => $sm?->id,
                    'membershipCode' => $sm?->membership_code,
                    'fullName' => $sm?->person?->full_name ?? '',
                    'nameEn' => $sm?->person?->name_en,
                    'roleLabelAm' => $dm->role_label_am,
                    'startsOn' => $dm->starts_on?->toDateString(),
                    'allDepartments' => $sm !== null
                        ? $sm->departmentMemberships
                            ->filter(fn (DepartmentMembership $item): bool => $item->isCurrentlyEffective())
                            ->map(fn ($item): string => app()->getLocale() === 'en'
                                ? ($item->organizationalUnit?->name_en ?: ($item->organizationalUnit?->name_am ?? ''))
                                : ($item->organizationalUnit?->name_am ?? ''))
                            ->filter()
                            ->values()
                            ->all()
                        : [],
                ];
            })
            ->values()
            ->all();

        $reports = DemoReport::query()
            ->where('organizational_unit_id', $organizationalUnit->id)
            ->with(['organization', 'organizationalUnit', 'submittedBy:id,name', 'approvedBy:id,name'])
            ->orderByDesc('id')
            ->get()
            ->filter(fn (DemoReport $report): bool => $scope->canViewReport($report))
            ->map(fn (DemoReport $report): array => $this->formatReportSummary($report, $scope))
            ->values()
            ->all();

        return Inertia::render('departments/Show', [
            'department' => [
                'id' => $organizationalUnit->id,
                'code' => $organizationalUnit->code,
                'categoryCode' => $organizationalUnit->category_code,
                'nameAm' => $organizationalUnit->name_am,
                'nameEn' => $organizationalUnit->name_en,
                'schoolId' => $organizationalUnit->organization_id,
                'schoolNameAm' => $organizationalUnit->organization?->name_am,
                'schoolNameEn' => $organizationalUnit->organization?->name_en,
                'parishNameAm' => $organizationalUnit->organization?->parish_name_am,
                'parishNameEn' => $organizationalUnit->organization?->parish_name_en,
                'cityAm' => $organizationalUnit->organization?->city_am,
                'cityEn' => $organizationalUnit->organization?->city_en,
            ],
            'members' => $members,
            'reports' => $reports,
            'scopeSummary' => $scope->toInertiaPayload(),
        ]);
    }

    public function showMember(Request $request, SchoolMembership $schoolMembership): Response
    {
        $scope = HierarchyScopeContext::resolveForRequest($request);
        abort_unless($scope->canViewMembershipRecord($schoolMembership), 403, 'Access denied: this member record is outside your authorized school or department scope.');

        $schoolMembership->load([
            'person',
            'organization.units',
            'user:id,name,email',
            'pendingUser:id,name,email',
            'approvedBy:id,name',
            'rejectedBy:id,name',
            'suspendedBy:id,name',
            'archivedBy:id,name',
            'departmentMemberships.organizationalUnit',
            'departmentMemberships.assignedBy:id,name',
            'departmentMemberships.endedBy:id,name',
            'decisions.actor:id,name',
            'decisions.targetUser:id,name,email',
            'invitations.createdBy:id,name',
            'invitations.acceptedBy:id,name,email',
            'invitations.reviewedBy:id,name',
        ]);

        $canManageMember = $scope->canManageMembershipLifecycle($schoolMembership);

        $availableDepartments = $canManageMember && $schoolMembership->organization !== null
            ? $schoolMembership->organization->units
                ->where('unit_kind', OrganizationalUnit::KIND_DEPARTMENT)
                ->map(fn (OrganizationalUnit $unit): array => [
                    'id' => $unit->id,
                    'code' => $unit->code,
                    'nameAm' => $unit->name_am,
                    'nameEn' => $unit->name_en,
                ])
                ->values()
                ->all()
            : [];

        $activeUnitIds = $schoolMembership->departmentMemberships
            ->where('is_active', true)
            ->pluck('organizational_unit_id')
            ->unique()
            ->all();

        $memberAllowsDepartmentAssignment = in_array($schoolMembership->status, [
            SchoolMembership::STATUS_APPROVED,
            SchoolMembership::STATUS_PENDING,
        ], true);

        $departmentParticipations = $schoolMembership->departmentMemberships
            ->sortByDesc(fn (DepartmentMembership $dm): int => ($dm->is_active ? 1000000 : 0) + $dm->id)
            ->map(fn (DepartmentMembership $dm): array => [
                'id' => $dm->id,
                'unitId' => $dm->organizational_unit_id,
                'nameAm' => $dm->organizationalUnit?->name_am ?? '',
                'nameEn' => $dm->organizationalUnit?->name_en,
                'roleLabelAm' => $dm->role_label_am,
                'startsOn' => $dm->starts_on?->toDateString(),
                'endsOn' => $dm->ends_on?->toDateString(),
                'isActive' => $dm->is_active,
                'isCurrentlyEffective' => $dm->isCurrentlyEffective(),
                'canStartNewPeriod' => ! $dm->is_active
                    && $memberAllowsDepartmentAssignment
                    && ! in_array($dm->organizational_unit_id, $activeUnitIds, true),
                'assignedBy' => $dm->assignedBy?->name,
                'endedBy' => $dm->endedBy?->name,
            ])
            ->values()
            ->all();

        $decisions = $canManageMember || ($scope->membership !== null && (int) $scope->membership->id === (int) $schoolMembership->id)
            ? $schoolMembership->decisions
                ->map(fn (MembershipDecision $d): array => [
                    'id' => $d->id,
                    'action' => $d->action,
                    'fromStatus' => $d->from_status,
                    'toStatus' => $d->to_status,
                    'reason' => $d->reason,
                    'actorName' => $d->actor?->name,
                    'targetUserEmail' => $d->targetUser?->email,
                    'createdAt' => $d->created_at?->toDateTimeString(),
                ])
                ->values()
                ->all()
            : [];

        $invitations = $canManageMember
            ? $schoolMembership->invitations
                ->map(fn (MembershipInvitation $inv): array => [
                    'id' => $inv->id,
                    'invitedEmail' => $inv->invited_email,
                    'status' => $inv->effectiveStatus(),
                    'mailDeliveryStatus' => $inv->mail_delivery_status ?? MembershipInvitation::MAIL_PENDING,
                    'mailSentAt' => $inv->mail_sent_at?->toDateTimeString(),
                    'expiresAt' => $inv->expires_at?->toDateTimeString(),
                    'acceptedAt' => $inv->accepted_at?->toDateTimeString(),
                    'acceptedByName' => $inv->acceptedBy?->name,
                    'acceptedByEmail' => $inv->acceptedBy?->email,
                    'reviewedByName' => $inv->reviewedBy?->name,
                    'reviewedAt' => $inv->reviewed_at?->toDateTimeString(),
                    'reviewNote' => $inv->review_note,
                    'canApproveOrReject' => $inv->status === MembershipInvitation::STATUS_PENDING_LEAD_APPROVAL
                        && in_array($schoolMembership->status, [SchoolMembership::STATUS_APPROVED, SchoolMembership::STATUS_PENDING], true)
                        && $schoolMembership->user_id === null
                        && $schoolMembership->account_link_status === SchoolMembership::LINK_PENDING_LEAD_APPROVAL
                        && $schoolMembership->pending_user_id !== null
                        && (int) $schoolMembership->pending_user_id === (int) $inv->accepted_by_user_id,
                    'canResend' => in_array($inv->effectiveStatus(), [
                        MembershipInvitation::STATUS_PENDING_ACCEPTANCE,
                        MembershipInvitation::STATUS_EXPIRED,
                    ], true)
                        && in_array($schoolMembership->status, [SchoolMembership::STATUS_APPROVED, SchoolMembership::STATUS_PENDING], true)
                        && $schoolMembership->user_id === null
                        && $schoolMembership->pending_user_id === null,
                    'canRevoke' => in_array($inv->status, [
                        MembershipInvitation::STATUS_PENDING_ACCEPTANCE,
                        MembershipInvitation::STATUS_PENDING_LEAD_APPROVAL,
                    ], true),
                ])
                ->values()
                ->all()
            : [];

        return Inertia::render('members/Show', [
            'member' => [
                'id' => $schoolMembership->id,
                'membershipCode' => $schoolMembership->membership_code,
                'status' => $schoolMembership->status,
                'accountLinkStatus' => $schoolMembership->account_link_status,
                'statusReason' => $schoolMembership->status_reason,
                'fullName' => $schoolMembership->person?->full_name ?? '',
                'nameEn' => $schoolMembership->person?->name_en,
                'phone' => $schoolMembership->person?->phone,
                'serviceNotes' => $schoolMembership->person?->service_notes,
                'joinedOn' => $schoolMembership->joined_on?->toDateString(),
                'endedOn' => $schoolMembership->ended_on?->toDateString(),
                'schoolId' => $schoolMembership->organization_id,
                'schoolNameAm' => $schoolMembership->organization?->name_am,
                'schoolNameEn' => $schoolMembership->organization?->name_en,
                'accountEmail' => $schoolMembership->user?->email,
                'pendingAccountEmail' => $schoolMembership->pendingUser?->email,
                'applicationNote' => $schoolMembership->application_note,
                'approvedBy' => $schoolMembership->approvedBy?->name,
                'approvedAt' => $schoolMembership->approved_at?->toDateTimeString(),
                'rejectedBy' => $schoolMembership->rejectedBy?->name,
                'rejectedAt' => $schoolMembership->rejected_at?->toDateTimeString(),
                'suspendedBy' => $schoolMembership->suspendedBy?->name,
                'suspendedAt' => $schoolMembership->suspended_at?->toDateTimeString(),
                'archivedBy' => $schoolMembership->archivedBy?->name,
                'archivedAt' => $schoolMembership->archived_at?->toDateTimeString(),
                'departments' => $schoolMembership->departmentMemberships
                    ->filter(fn (DepartmentMembership $dm): bool => $dm->isCurrentlyEffective())
                    ->map(fn ($dm): array => [
                        'id' => $dm->organizational_unit_id,
                        'nameAm' => $dm->organizationalUnit?->name_am ?? '',
                        'nameEn' => $dm->organizationalUnit?->name_en,
                        'roleLabelAm' => $dm->role_label_am,
                    ])
                    ->values()
                    ->all(),
                'departmentParticipations' => $departmentParticipations,
                'decisions' => $decisions,
                'invitations' => $invitations,
                'canApprove' => $schoolMembership->status === SchoolMembership::STATUS_PENDING && $scope->canApproveMembership($schoolMembership),
                'canManageMember' => $canManageMember,
            ],
            'availableDepartments' => $availableDepartments,
            'scopeSummary' => $scope->toInertiaPayload(),
        ]);
    }

    public function approveMembership(Request $request, SchoolMembership $schoolMembership): RedirectResponse
    {
        $request->merge([
            'action' => 'approve',
            'reason' => $request->input('reason'),
        ]);

        return $this->transitionMembershipStatus($request, $schoolMembership);
    }

    public function transitionMembershipStatus(Request $request, SchoolMembership $schoolMembership): RedirectResponse
    {
        $scope = HierarchyScopeContext::resolveForRequest($request);
        abort_unless(
            $scope->canManageMembershipLifecycle($schoolMembership),
            403,
            'Only the assigned local Sunday School lead can manage membership status and account-link transitions for this school.'
        );

        $validated = $request->validate([
            'action' => ['required', 'string', Rule::in(['approve', 'reject', 'suspend', 'archive', 'reactivate'])],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $action = $validated['action'];
        $reason = isset($validated['reason']) && trim($validated['reason']) !== ''
            ? trim($validated['reason'])
            : null;

        if (in_array($action, ['reject', 'suspend', 'archive'], true) && $reason === null) {
            throw ValidationException::withMessages([
                'reason' => [__('messages.demo.lifecycle.reason_required')],
            ]);
        }

        $actor = $request->user();

        DB::transaction(function () use ($request, $schoolMembership, $action, $reason, $actor): void {
            // Lock order: 1. SchoolMembership -> 2. MembershipInvitation -> 3. User / DepartmentMembership / ScopedAppointment
            $locked = SchoolMembership::query()
                ->whereKey($schoolMembership->id)
                ->lockForUpdate()
                ->firstOrFail();

            $fromStatus = $locked->status;

            if ($action === 'approve') {
                // Idempotent double-click handling
                if ($fromStatus === SchoolMembership::STATUS_APPROVED) {
                    return;
                }

                if ($fromStatus !== SchoolMembership::STATUS_PENDING) {
                    throw ValidationException::withMessages([
                        'action' => [__('messages.demo.lifecycle.invalid_transition')],
                    ]);
                }

                // Do NOT blindly approve a pending invitation link here; invitation links must be approved via approveInvitationLink()
                $linkedUserId = $locked->user_id;
                $accountLinkStatus = $locked->account_link_status;

                if ($linkedUserId !== null) {
                    $existingLinkedUser = User::query()->lockForUpdate()->find($linkedUserId);
                    if ($existingLinkedUser !== null && $existingLinkedUser->hasTechnicalIdentity()) {
                        throw ValidationException::withMessages([
                            'action' => [__('messages.demo.invitations.technical_incompatible')],
                        ]);
                    }
                    $accountLinkStatus = SchoolMembership::LINK_APPROVED;
                }

                $effectiveReason = $reason ?? __('messages.demo.decisions.default_approved_note');

                $locked->update([
                    'status' => SchoolMembership::STATUS_APPROVED,
                    'account_link_status' => $accountLinkStatus,
                    'status_reason' => $effectiveReason,
                    'approved_by_user_id' => $actor?->id,
                    'approved_at' => now(),
                    'joined_on' => $locked->joined_on ?? now()->toDateString(),
                    'ended_on' => null,
                ]);

                MembershipDecision::query()->create([
                    'school_membership_id' => $locked->id,
                    'organization_id' => $locked->organization_id,
                    'actor_user_id' => $actor?->id,
                    'target_user_id' => $linkedUserId,
                    'action' => MembershipDecision::ACTION_APPROVED,
                    'from_status' => $fromStatus,
                    'to_status' => SchoolMembership::STATUS_APPROVED,
                    'reason' => $effectiveReason,
                ]);

                ActivityLogger::record(
                    actor: $actor,
                    event: 'memberships.approved',
                    description: "Approved school membership #{$locked->id} in organization #{$locked->organization_id}.",
                    subject: $locked,
                    request: $request,
                );

                return;
            }

            if ($action === 'reject') {
                if ($fromStatus === SchoolMembership::STATUS_REJECTED) {
                    return;
                }

                if ($fromStatus !== SchoolMembership::STATUS_PENDING) {
                    throw ValidationException::withMessages([
                        'action' => [__('messages.demo.lifecycle.invalid_transition')],
                    ]);
                }

                $openInvitations = MembershipInvitation::query()
                    ->where('school_membership_id', $locked->id)
                    ->whereIn('status', [
                        MembershipInvitation::STATUS_PENDING_ACCEPTANCE,
                        MembershipInvitation::STATUS_PENDING_LEAD_APPROVAL,
                    ])
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();

                foreach ($openInvitations as $openInv) {
                    $openInv->update([
                        'status' => MembershipInvitation::STATUS_REJECTED,
                        'reviewed_by_user_id' => $actor?->id,
                        'reviewed_at' => now(),
                        'review_note' => $reason,
                    ]);
                }

                $locked->update([
                    'status' => SchoolMembership::STATUS_REJECTED,
                    'pending_user_id' => null,
                    'account_link_status' => $locked->user_id !== null ? SchoolMembership::LINK_REJECTED : SchoolMembership::LINK_NONE,
                    'status_reason' => $reason,
                    'rejected_by_user_id' => $actor?->id,
                    'rejected_at' => now(),
                ]);

                MembershipDecision::query()->create([
                    'school_membership_id' => $locked->id,
                    'organization_id' => $locked->organization_id,
                    'actor_user_id' => $actor?->id,
                    'target_user_id' => $locked->user_id,
                    'action' => MembershipDecision::ACTION_REJECTED,
                    'from_status' => $fromStatus,
                    'to_status' => SchoolMembership::STATUS_REJECTED,
                    'reason' => $reason,
                ]);

                ActivityLogger::record(
                    actor: $actor,
                    event: 'memberships.rejected',
                    description: "Rejected school membership #{$locked->id} in organization #{$locked->organization_id}.",
                    subject: $locked,
                    request: $request,
                );

                return;
            }

            if ($action === 'suspend') {
                if ($fromStatus === SchoolMembership::STATUS_SUSPENDED) {
                    return;
                }

                if ($fromStatus !== SchoolMembership::STATUS_APPROVED) {
                    throw ValidationException::withMessages([
                        'action' => [__('messages.demo.lifecycle.invalid_transition')],
                    ]);
                }

                // Revoke any open invitations so a suspended membership cannot retain a stale pending link
                $openInvitations = MembershipInvitation::query()
                    ->where('school_membership_id', $locked->id)
                    ->whereIn('status', [
                        MembershipInvitation::STATUS_PENDING_ACCEPTANCE,
                        MembershipInvitation::STATUS_PENDING_LEAD_APPROVAL,
                    ])
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();

                foreach ($openInvitations as $openInv) {
                    $openInv->update([
                        'status' => MembershipInvitation::STATUS_REVOKED,
                        'revoked_at' => now(),
                        'revoked_by_user_id' => $actor?->id,
                        'review_note' => $reason,
                    ]);
                }

                $locked->update([
                    'status' => SchoolMembership::STATUS_SUSPENDED,
                    'pending_user_id' => null,
                    'account_link_status' => $locked->user_id !== null ? SchoolMembership::LINK_APPROVED : SchoolMembership::LINK_NONE,
                    'status_reason' => $reason,
                    'suspended_by_user_id' => $actor?->id,
                    'suspended_at' => now(),
                ]);

                // Automatically deactivate any active department_coordinator appointments for this member in this school
                if ($locked->user_id !== null) {
                    ScopedAppointment::query()
                        ->where('organization_id', $locked->organization_id)
                        ->where('user_id', $locked->user_id)
                        ->where('role_key', ScopedAppointment::ROLE_DEPARTMENT_COORDINATOR)
                        ->where('is_active', true)
                        ->update([
                            'is_active' => false,
                            'ends_on' => now()->toDateString(),
                        ]);
                }

                MembershipDecision::query()->create([
                    'school_membership_id' => $locked->id,
                    'organization_id' => $locked->organization_id,
                    'actor_user_id' => $actor?->id,
                    'target_user_id' => $locked->user_id,
                    'action' => MembershipDecision::ACTION_SUSPENDED,
                    'from_status' => $fromStatus,
                    'to_status' => SchoolMembership::STATUS_SUSPENDED,
                    'reason' => $reason,
                ]);

                ActivityLogger::record(
                    actor: $actor,
                    event: 'memberships.suspended',
                    description: "Suspended school membership #{$locked->id} in organization #{$locked->organization_id}.",
                    subject: $locked,
                    request: $request,
                );

                return;
            }

            if ($action === 'archive') {
                if ($fromStatus === SchoolMembership::STATUS_ARCHIVED) {
                    return;
                }

                if (! in_array($fromStatus, [
                    SchoolMembership::STATUS_APPROVED,
                    SchoolMembership::STATUS_SUSPENDED,
                    SchoolMembership::STATUS_REJECTED,
                ], true)) {
                    throw ValidationException::withMessages([
                        'action' => [__('messages.demo.lifecycle.invalid_transition')],
                    ]);
                }

                $openInvitations = MembershipInvitation::query()
                    ->where('school_membership_id', $locked->id)
                    ->whereIn('status', [
                        MembershipInvitation::STATUS_PENDING_ACCEPTANCE,
                        MembershipInvitation::STATUS_PENDING_LEAD_APPROVAL,
                    ])
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();

                foreach ($openInvitations as $openInv) {
                    $openInv->update([
                        'status' => MembershipInvitation::STATUS_REVOKED,
                        'revoked_at' => now(),
                        'revoked_by_user_id' => $actor?->id,
                        'review_note' => $reason,
                    ]);
                }

                $today = now()->toDateString();

                $locked->update([
                    'status' => SchoolMembership::STATUS_ARCHIVED,
                    'pending_user_id' => null,
                    'account_link_status' => $locked->user_id !== null ? SchoolMembership::LINK_APPROVED : SchoolMembership::LINK_NONE,
                    'status_reason' => $reason,
                    'archived_by_user_id' => $actor?->id,
                    'archived_at' => now(),
                    'ended_on' => $locked->ended_on ?? $today,
                ]);

                // End active department participations while preserving historical start dates and rows
                $activeDepartmentMemberships = DepartmentMembership::query()
                    ->where('school_membership_id', $locked->id)
                    ->where('is_active', true)
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();

                foreach ($activeDepartmentMemberships as $activeDm) {
                    $dmStartsOn = $activeDm->starts_on?->toDateString();
                    $dmEndsOn = ($dmStartsOn !== null && $dmStartsOn > $today) ? $dmStartsOn : $today;
                    $activeDm->update([
                        'is_active' => false,
                        'ends_on' => $dmEndsOn,
                        'ended_by_user_id' => $actor?->id,
                    ]);
                }

                // Deactivate any active department_coordinator appointments for this member in this school
                if ($locked->user_id !== null) {
                    ScopedAppointment::query()
                        ->where('organization_id', $locked->organization_id)
                        ->where('user_id', $locked->user_id)
                        ->where('role_key', ScopedAppointment::ROLE_DEPARTMENT_COORDINATOR)
                        ->where('is_active', true)
                        ->update([
                            'is_active' => false,
                            'ends_on' => $today,
                        ]);
                }

                MembershipDecision::query()->create([
                    'school_membership_id' => $locked->id,
                    'organization_id' => $locked->organization_id,
                    'actor_user_id' => $actor?->id,
                    'target_user_id' => $locked->user_id,
                    'action' => MembershipDecision::ACTION_ARCHIVED,
                    'from_status' => $fromStatus,
                    'to_status' => SchoolMembership::STATUS_ARCHIVED,
                    'reason' => $reason,
                ]);

                ActivityLogger::record(
                    actor: $actor,
                    event: 'memberships.archived',
                    description: "Archived school membership #{$locked->id} in organization #{$locked->organization_id}.",
                    subject: $locked,
                    request: $request,
                );

                return;
            }

            if ($action === 'reactivate') {
                if ($fromStatus === SchoolMembership::STATUS_APPROVED) {
                    return;
                }

                if (! in_array($fromStatus, [
                    SchoolMembership::STATUS_SUSPENDED,
                    SchoolMembership::STATUS_ARCHIVED,
                    SchoolMembership::STATUS_REJECTED,
                ], true)) {
                    throw ValidationException::withMessages([
                        'action' => [__('messages.demo.lifecycle.invalid_transition')],
                    ]);
                }

                $effectiveReason = $reason ?? __('messages.demo.decisions.default_reactivated_note');

                $locked->update([
                    'status' => SchoolMembership::STATUS_APPROVED,
                    'pending_user_id' => null,
                    'account_link_status' => $locked->user_id !== null ? SchoolMembership::LINK_APPROVED : SchoolMembership::LINK_NONE,
                    'status_reason' => $effectiveReason,
                    'approved_by_user_id' => $actor?->id,
                    'approved_at' => now(),
                    'joined_on' => $locked->joined_on ?? now()->toDateString(),
                    'ended_on' => null,
                ]);

                MembershipDecision::query()->create([
                    'school_membership_id' => $locked->id,
                    'organization_id' => $locked->organization_id,
                    'actor_user_id' => $actor?->id,
                    'target_user_id' => $locked->user_id,
                    'action' => MembershipDecision::ACTION_REACTIVATED,
                    'from_status' => $fromStatus,
                    'to_status' => SchoolMembership::STATUS_APPROVED,
                    'reason' => $effectiveReason,
                ]);

                ActivityLogger::record(
                    actor: $actor,
                    event: 'memberships.reactivated',
                    description: "Reactivated school membership #{$locked->id} in organization #{$locked->organization_id}.",
                    subject: $locked,
                    request: $request,
                );
            }
        });

        $flashKey = match ($action) {
            'approve' => 'messages.demo.flash.membership_approved',
            'reject' => 'messages.demo.flash.membership_rejected',
            'suspend' => 'messages.demo.flash.membership_suspended',
            'archive' => 'messages.demo.flash.membership_archived',
            default => 'messages.demo.flash.membership_reactivated',
        };

        return back()->with('success', __($flashKey));
    }

    public function storeInvitation(Request $request, Organization $organization): RedirectResponse
    {
        $scope = HierarchyScopeContext::resolveForRequest($request);
        abort_unless(
            $scope->canManageSchoolInvitations($organization),
            403,
            'Only the assigned local Sunday School lead can issue member account-link invitations for this school.'
        );

        $validated = $request->validate([
            'school_membership_id' => [
                'required',
                'integer',
                Rule::exists('school_memberships', 'id')->where('organization_id', $organization->id),
            ],
            'invited_email' => ['required', 'email', 'max:255'],
        ]);

        $invitedEmailLower = mb_strtolower(trim($validated['invited_email']));
        $existingUser = User::query()->whereRaw('LOWER(email) = ?', [$invitedEmailLower])->first();
        if ($existingUser !== null && $existingUser->hasTechnicalIdentity()) {
            throw ValidationException::withMessages([
                'invited_email' => [__('messages.demo.invitations.technical_incompatible')],
            ]);
        }

        $actor = $request->user();

        try {
            /** @var array{membership: SchoolMembership, invitation: MembershipInvitation, rawToken: string} $issued */
            $issued = DB::transaction(function () use ($organization, $validated, $invitedEmailLower, $existingUser, $actor, $request): array {
                // Lock order: 1. SchoolMembership -> 2. MembershipInvitation -> 3. User
                $membership = SchoolMembership::query()
                    ->with('person')
                    ->whereKey($validated['school_membership_id'])
                    ->where('organization_id', $organization->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (! in_array($membership->status, [SchoolMembership::STATUS_APPROVED, SchoolMembership::STATUS_PENDING], true)) {
                    throw ValidationException::withMessages([
                        'invited_email' => [__('messages.demo.invitations.error_membership_not_eligible')],
                    ]);
                }

                if ($membership->user_id !== null || $membership->account_link_status === SchoolMembership::LINK_APPROVED) {
                    throw ValidationException::withMessages([
                        'invited_email' => [__('messages.demo.invitations.error_already_linked')],
                    ]);
                }

                if ($membership->pending_user_id !== null || $membership->account_link_status === SchoolMembership::LINK_PENDING_LEAD_APPROVAL) {
                    throw ValidationException::withMessages([
                        'invited_email' => [__('messages.demo.invitations.error_pending_lead_review_exists')],
                    ]);
                }

                $hasPendingLeadInvitation = MembershipInvitation::query()
                    ->where('school_membership_id', $membership->id)
                    ->where('status', MembershipInvitation::STATUS_PENDING_LEAD_APPROVAL)
                    ->lockForUpdate()
                    ->exists();

                if ($hasPendingLeadInvitation) {
                    throw ValidationException::withMessages([
                        'invited_email' => [__('messages.demo.invitations.error_pending_lead_review_exists')],
                    ]);
                }

                if ($existingUser !== null) {
                    $alreadyInSameSchool = SchoolMembership::query()
                        ->where('organization_id', $organization->id)
                        ->where('id', '!=', $membership->id)
                        ->where(function (Builder $q) use ($existingUser): void {
                            $q->where('user_id', $existingUser->id)
                                ->orWhere('pending_user_id', $existingUser->id);
                        })
                        ->lockForUpdate()
                        ->exists();

                    if ($alreadyInSameSchool) {
                        throw ValidationException::withMessages([
                            'invited_email' => [__('messages.demo.invitations.error_user_already_member_same_school')],
                        ]);
                    }
                }

                $created = $this->issueInvitationForMembership(
                    school: $organization,
                    membership: $membership,
                    invitedEmail: $invitedEmailLower,
                    actor: $actor,
                    request: $request,
                );

                return [
                    'membership' => $membership,
                    'invitation' => $created['invitation'],
                    'rawToken' => $created['rawToken'],
                ];
            });
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages([
                'invited_email' => [__('messages.demo.invitations.error_pending_lead_review_exists')],
            ]);
        }

        $mailSent = $this->deliverCommittedInvitationMail(
            invitation: $issued['invitation'],
            school: $organization,
            membership: $issued['membership'],
            rawToken: $issued['rawToken'],
            actor: $actor,
            request: $request,
        );

        if (! $mailSent) {
            return back()->with('error', __('messages.demo.flash.invitation_mail_failed', [
                'email' => $invitedEmailLower,
            ]));
        }

        return back()->with('success', __('messages.demo.flash.invitation_sent', [
            'email' => $invitedEmailLower,
        ]));
    }

    public function resendInvitation(Request $request, MembershipInvitation $membershipInvitation): RedirectResponse
    {
        $scope = HierarchyScopeContext::resolveForRequest($request);
        $membershipInvitation->loadMissing(['organization', 'schoolMembership.person']);

        abort_unless(
            $membershipInvitation->organization !== null
            && $scope->canManageSchoolInvitations($membershipInvitation->organization),
            403,
            'Only the assigned local Sunday School lead can resend member invitations for this school.'
        );

        $actor = $request->user();
        $school = $membershipInvitation->organization;

        try {
            /** @var array{membership: SchoolMembership, invitation: MembershipInvitation, rawToken: string} $resent */
            $resent = DB::transaction(function () use ($request, $membershipInvitation, $actor): array {
                // Lock order: 1. SchoolMembership -> 2. MembershipInvitation
                $membership = SchoolMembership::query()
                    ->with('person')
                    ->whereKey($membershipInvitation->school_membership_id)
                    ->where('organization_id', $membershipInvitation->organization_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $lockedInv = MembershipInvitation::query()
                    ->whereKey($membershipInvitation->id)
                    ->where('school_membership_id', $membership->id)
                    ->where('organization_id', $membership->organization_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (! in_array($membership->status, [SchoolMembership::STATUS_APPROVED, SchoolMembership::STATUS_PENDING], true)) {
                    throw ValidationException::withMessages([
                        'invitation' => [__('messages.demo.invitations.error_membership_not_eligible')],
                    ]);
                }

                if ($membership->user_id !== null || $membership->account_link_status === SchoolMembership::LINK_APPROVED) {
                    throw ValidationException::withMessages([
                        'invitation' => [__('messages.demo.invitations.error_already_linked')],
                    ]);
                }

                if ($membership->pending_user_id !== null || $membership->account_link_status === SchoolMembership::LINK_PENDING_LEAD_APPROVAL) {
                    throw ValidationException::withMessages([
                        'invitation' => [__('messages.demo.invitations.error_pending_lead_review_exists')],
                    ]);
                }

                if (! in_array($lockedInv->status, [
                    MembershipInvitation::STATUS_PENDING_ACCEPTANCE,
                    MembershipInvitation::STATUS_EXPIRED,
                ], true)) {
                    throw ValidationException::withMessages([
                        'invitation' => [__('messages.demo.invitations.error_cannot_resend')],
                    ]);
                }

                $rawToken = Str::random(48);
                $tokenHash = hash('sha256', $rawToken);

                $lockedInv->update([
                    'token_hash' => $tokenHash,
                    'status' => MembershipInvitation::STATUS_PENDING_ACCEPTANCE,
                    'mail_delivery_status' => MembershipInvitation::MAIL_PENDING,
                    'expires_at' => now()->addDays(7),
                    'mail_error' => null,
                ]);

                $membership->update([
                    'account_link_status' => SchoolMembership::LINK_PENDING_ACCEPTANCE,
                ]);

                MembershipDecision::query()->create([
                    'school_membership_id' => $membership->id,
                    'organization_id' => $membership->organization_id,
                    'actor_user_id' => $actor?->id,
                    'target_user_id' => null,
                    'action' => MembershipDecision::ACTION_INVITATION_RESENT,
                    'from_status' => $membership->status,
                    'to_status' => $membership->status,
                    'reason' => __('messages.demo.decisions.default_invitation_resent_note', [
                        'email' => $lockedInv->invited_email,
                    ]),
                ]);

                ActivityLogger::record(
                    actor: $actor,
                    event: 'invitations.resent',
                    description: "Resent membership invitation #{$lockedInv->id} for membership #{$membership->id} in organization #{$membership->organization_id}.",
                    subject: $lockedInv,
                    properties: [
                        'invitation_id' => $lockedInv->id,
                        'school_membership_id' => $membership->id,
                        'organization_id' => $membership->organization_id,
                    ],
                    request: $request,
                );

                return [
                    'membership' => $membership,
                    'invitation' => $lockedInv,
                    'rawToken' => $rawToken,
                ];
            });
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages([
                'invitation' => [__('messages.demo.invitations.error_pending_lead_review_exists')],
            ]);
        }

        $mailSent = $this->deliverCommittedInvitationMail(
            invitation: $resent['invitation'],
            school: $school,
            membership: $resent['membership'],
            rawToken: $resent['rawToken'],
            actor: $actor,
            request: $request,
        );

        if (! $mailSent) {
            return back()->with('error', __('messages.demo.flash.invitation_mail_failed', [
                'email' => $resent['invitation']->invited_email,
            ]));
        }

        return back()->with('success', __('messages.demo.flash.invitation_resent', [
            'email' => $resent['invitation']->invited_email,
        ]));
    }

    public function revokeInvitation(Request $request, MembershipInvitation $membershipInvitation): RedirectResponse
    {
        $scope = HierarchyScopeContext::resolveForRequest($request);
        $membershipInvitation->loadMissing(['organization', 'schoolMembership']);

        abort_unless(
            $membershipInvitation->organization !== null
            && $scope->canManageSchoolInvitations($membershipInvitation->organization),
            403,
            'Only the assigned local Sunday School lead can revoke invitations for this school.'
        );

        $validated = $request->validate([
            'review_note' => ['nullable', 'string', 'max:500'],
        ]);

        $actor = $request->user();
        $note = isset($validated['review_note']) && trim($validated['review_note']) !== ''
            ? trim($validated['review_note'])
            : __('messages.demo.decisions.default_invitation_revoked_note');

        DB::transaction(function () use ($request, $membershipInvitation, $note, $actor): void {
            // Lock order: 1. SchoolMembership -> 2. MembershipInvitation
            $membership = SchoolMembership::query()
                ->whereKey($membershipInvitation->school_membership_id)
                ->where('organization_id', $membershipInvitation->organization_id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedInv = MembershipInvitation::query()
                ->whereKey($membershipInvitation->id)
                ->where('school_membership_id', $membership->id)
                ->where('organization_id', $membership->organization_id)
                ->lockForUpdate()
                ->firstOrFail();

            // Idempotent double-click handling
            if ($lockedInv->status === MembershipInvitation::STATUS_REVOKED) {
                return;
            }

            if (! in_array($lockedInv->status, [
                MembershipInvitation::STATUS_PENDING_ACCEPTANCE,
                MembershipInvitation::STATUS_PENDING_LEAD_APPROVAL,
            ], true)) {
                throw ValidationException::withMessages([
                    'invitation' => [__('messages.demo.invitations.error_cannot_revoke')],
                ]);
            }

            $priorInvitationStatus = $lockedInv->status;

            $lockedInv->update([
                'status' => MembershipInvitation::STATUS_REVOKED,
                'revoked_at' => now(),
                'revoked_by_user_id' => $actor?->id,
                'review_note' => $note,
            ]);

            $matchesCurrentPendingLink = $membership->user_id === null && (
                ($priorInvitationStatus === MembershipInvitation::STATUS_PENDING_ACCEPTANCE
                    && $membership->account_link_status === SchoolMembership::LINK_PENDING_ACCEPTANCE
                    && $membership->pending_user_id === null)
                || ($priorInvitationStatus === MembershipInvitation::STATUS_PENDING_LEAD_APPROVAL
                    && $membership->account_link_status === SchoolMembership::LINK_PENDING_LEAD_APPROVAL
                    && $membership->pending_user_id !== null
                    && (int) $membership->pending_user_id === (int) $lockedInv->accepted_by_user_id)
            );

            if ($matchesCurrentPendingLink) {
                $membership->update([
                    'pending_user_id' => null,
                    'account_link_status' => SchoolMembership::LINK_NONE,
                ]);
            }

            MembershipDecision::query()->create([
                'school_membership_id' => $membership->id,
                'organization_id' => $membership->organization_id,
                'actor_user_id' => $actor?->id,
                'target_user_id' => $lockedInv->accepted_by_user_id,
                'action' => MembershipDecision::ACTION_INVITATION_REVOKED,
                'from_status' => $membership->status,
                'to_status' => $membership->status,
                'reason' => $note,
            ]);

            ActivityLogger::record(
                actor: $actor,
                event: 'invitations.revoked',
                description: "Revoked membership invitation #{$lockedInv->id} in organization #{$lockedInv->organization_id}.",
                subject: $lockedInv,
                request: $request,
            );
        });

        return back()->with('success', __('messages.demo.flash.invitation_revoked'));
    }

    public function approveInvitationLink(Request $request, MembershipInvitation $membershipInvitation): RedirectResponse
    {
        $scope = HierarchyScopeContext::resolveForRequest($request);
        $membershipInvitation->loadMissing(['organization', 'schoolMembership']);

        abort_unless(
            $membershipInvitation->organization !== null
            && $scope->canManageSchoolInvitations($membershipInvitation->organization),
            403,
            'Only the assigned local Sunday School lead can approve member account-link requests for this school.'
        );

        $validated = $request->validate([
            'review_note' => ['nullable', 'string', 'max:500'],
        ]);

        $actor = $request->user();
        $note = isset($validated['review_note']) && trim($validated['review_note']) !== ''
            ? trim($validated['review_note'])
            : __('messages.demo.decisions.default_link_approved_note');

        $deniedTechnicalUser = null;

        try {
            DB::transaction(function () use ($request, $membershipInvitation, $note, $actor, &$deniedTechnicalUser): void {
                // Lock order: 1. SchoolMembership -> 2. MembershipInvitation -> 3. User
                $membership = SchoolMembership::query()
                    ->whereKey($membershipInvitation->school_membership_id)
                    ->where('organization_id', $membershipInvitation->organization_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $lockedInv = MembershipInvitation::query()
                    ->whereKey($membershipInvitation->id)
                    ->where('school_membership_id', $membership->id)
                    ->where('organization_id', $membership->organization_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                // Idempotent double-click handling
                if (
                    $lockedInv->status === MembershipInvitation::STATUS_APPROVED
                    && $membership->account_link_status === SchoolMembership::LINK_APPROVED
                    && $lockedInv->accepted_by_user_id !== null
                    && (int) $membership->user_id === (int) $lockedInv->accepted_by_user_id
                ) {
                    return;
                }

                if ($lockedInv->status !== MembershipInvitation::STATUS_PENDING_LEAD_APPROVAL || $lockedInv->accepted_by_user_id === null) {
                    throw ValidationException::withMessages([
                        'invitation' => [__('messages.demo.invitations.error_not_ready_for_approval')],
                    ]);
                }

                if (! in_array($membership->status, [SchoolMembership::STATUS_APPROVED, SchoolMembership::STATUS_PENDING], true)) {
                    throw ValidationException::withMessages([
                        'invitation' => [__('messages.demo.invitations.error_membership_not_eligible')],
                    ]);
                }

                if ($membership->user_id !== null || $membership->account_link_status === SchoolMembership::LINK_APPROVED) {
                    throw ValidationException::withMessages([
                        'invitation' => [__('messages.demo.invitations.error_already_linked')],
                    ]);
                }

                // Require the invitation's accepted user to equal the membership's current pending link request
                if (
                    $membership->account_link_status !== SchoolMembership::LINK_PENDING_LEAD_APPROVAL
                    || $membership->pending_user_id === null
                    || (int) $membership->pending_user_id !== (int) $lockedInv->accepted_by_user_id
                ) {
                    throw ValidationException::withMessages([
                        'invitation' => [__('messages.demo.invitations.error_stale_invitation_mismatch')],
                    ]);
                }

                $targetUser = User::query()
                    ->whereKey($lockedInv->accepted_by_user_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($targetUser->hasTechnicalIdentity()) {
                    $deniedTechnicalUser = $targetUser;

                    return;
                }

                // Verify targetUser is not already linked or pending-linked in the same school or another school
                $sameSchoolDuplicate = SchoolMembership::query()
                    ->where('organization_id', $membership->organization_id)
                    ->where('id', '!=', $membership->id)
                    ->where(function (Builder $q) use ($targetUser): void {
                        $q->where('user_id', $targetUser->id)
                            ->orWhere('pending_user_id', $targetUser->id);
                    })
                    ->lockForUpdate()
                    ->exists();

                if ($sameSchoolDuplicate) {
                    throw ValidationException::withMessages([
                        'invitation' => [__('messages.demo.invitations.error_user_already_member_same_school')],
                    ]);
                }

                $otherSchoolDuplicate = SchoolMembership::query()
                    ->where('organization_id', '!=', $membership->organization_id)
                    ->where(function (Builder $q) use ($targetUser): void {
                        $q->where('user_id', $targetUser->id)
                            ->orWhere('pending_user_id', $targetUser->id);
                    })
                    ->lockForUpdate()
                    ->exists();

                if ($otherSchoolDuplicate) {
                    throw ValidationException::withMessages([
                        'invitation' => [__('messages.demo.invitations.error_cross_school_claim_deferred')],
                    ]);
                }

                $oldStatus = $membership->status;
                $newStatus = $oldStatus === SchoolMembership::STATUS_PENDING
                    ? SchoolMembership::STATUS_APPROVED
                    : $oldStatus;

                $membership->update([
                    'user_id' => $targetUser->id,
                    'pending_user_id' => null,
                    'account_link_status' => SchoolMembership::LINK_APPROVED,
                    'status' => $newStatus,
                    'approved_by_user_id' => $membership->approved_by_user_id ?? $actor?->id,
                    'approved_at' => $membership->approved_at ?? now(),
                    'joined_on' => $membership->joined_on ?? now()->toDateString(),
                ]);

                $lockedInv->update([
                    'status' => MembershipInvitation::STATUS_APPROVED,
                    'reviewed_by_user_id' => $actor?->id,
                    'reviewed_at' => now(),
                    'review_note' => $note,
                ]);

                MembershipDecision::query()->create([
                    'school_membership_id' => $membership->id,
                    'organization_id' => $membership->organization_id,
                    'actor_user_id' => $actor?->id,
                    'target_user_id' => $targetUser->id,
                    'action' => MembershipDecision::ACTION_ACCOUNT_LINK_APPROVED,
                    'from_status' => $oldStatus,
                    'to_status' => $newStatus,
                    'reason' => $note,
                ]);

                ActivityLogger::record(
                    actor: $actor,
                    event: 'invitations.link-approved',
                    description: "Approved account link for membership #{$membership->id} via invitation #{$lockedInv->id}.",
                    subject: $membership,
                    properties: [
                        'invitation_id' => $lockedInv->id,
                        'school_membership_id' => $membership->id,
                        'organization_id' => $membership->organization_id,
                        'linked_user_id' => $targetUser->id,
                    ],
                    request: $request,
                );
            });
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages([
                'invitation' => [__('messages.demo.invitations.error_user_already_member_same_school')],
            ]);
        }

        if ($deniedTechnicalUser !== null) {
            ActivityLogger::record(
                actor: $actor,
                event: 'appointments.technical-incompatibility-denied',
                description: "Denied account-link approval for technical identity {$deniedTechnicalUser->email} (#{$deniedTechnicalUser->id}).",
                subject: $deniedTechnicalUser,
                request: $request,
            );

            throw ValidationException::withMessages([
                'invitation' => [__('messages.demo.invitations.technical_incompatible')],
            ]);
        }

        return back()->with('success', __('messages.demo.flash.invitation_link_approved'));
    }

    public function rejectInvitationLink(Request $request, MembershipInvitation $membershipInvitation): RedirectResponse
    {
        $scope = HierarchyScopeContext::resolveForRequest($request);
        $membershipInvitation->loadMissing(['organization', 'schoolMembership']);

        abort_unless(
            $membershipInvitation->organization !== null
            && $scope->canManageSchoolInvitations($membershipInvitation->organization),
            403,
            'Only the assigned local Sunday School lead can reject member account-link requests for this school.'
        );

        $validated = $request->validate([
            'review_note' => ['nullable', 'string', 'max:500'],
        ]);

        $note = isset($validated['review_note']) && trim($validated['review_note']) !== ''
            ? trim($validated['review_note'])
            : null;

        if ($note === null) {
            throw ValidationException::withMessages([
                'review_note' => [__('messages.demo.invitations.reason_required')],
            ]);
        }

        $actor = $request->user();

        DB::transaction(function () use ($request, $membershipInvitation, $note, $actor): void {
            // Lock order: 1. SchoolMembership -> 2. MembershipInvitation
            $membership = SchoolMembership::query()
                ->whereKey($membershipInvitation->school_membership_id)
                ->where('organization_id', $membershipInvitation->organization_id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedInv = MembershipInvitation::query()
                ->whereKey($membershipInvitation->id)
                ->where('school_membership_id', $membership->id)
                ->where('organization_id', $membership->organization_id)
                ->lockForUpdate()
                ->firstOrFail();

            // Idempotent double-click handling
            if ($lockedInv->status === MembershipInvitation::STATUS_REJECTED && $membership->pending_user_id === null) {
                return;
            }

            if ($lockedInv->status !== MembershipInvitation::STATUS_PENDING_LEAD_APPROVAL || $lockedInv->accepted_by_user_id === null) {
                throw ValidationException::withMessages([
                    'invitation' => [__('messages.demo.invitations.error_not_ready_for_approval')],
                ]);
            }

            if ($membership->user_id !== null) {
                throw ValidationException::withMessages([
                    'invitation' => [__('messages.demo.invitations.error_already_linked')],
                ]);
            }

            if (
                $membership->account_link_status !== SchoolMembership::LINK_PENDING_LEAD_APPROVAL
                || $membership->pending_user_id === null
                || (int) $membership->pending_user_id !== (int) $lockedInv->accepted_by_user_id
            ) {
                throw ValidationException::withMessages([
                    'invitation' => [__('messages.demo.invitations.error_stale_invitation_mismatch')],
                ]);
            }

            $lockedInv->update([
                'status' => MembershipInvitation::STATUS_REJECTED,
                'reviewed_by_user_id' => $actor?->id,
                'reviewed_at' => now(),
                'review_note' => $note,
            ]);

            $membership->update([
                'pending_user_id' => null,
                'account_link_status' => SchoolMembership::LINK_REJECTED,
            ]);

            MembershipDecision::query()->create([
                'school_membership_id' => $membership->id,
                'organization_id' => $membership->organization_id,
                'actor_user_id' => $actor?->id,
                'target_user_id' => $lockedInv->accepted_by_user_id,
                'action' => MembershipDecision::ACTION_ACCOUNT_LINK_REJECTED,
                'from_status' => $membership->status,
                'to_status' => $membership->status,
                'reason' => $note,
            ]);

            ActivityLogger::record(
                actor: $actor,
                event: 'invitations.link-rejected',
                description: "Rejected account link for membership #{$membership->id} via invitation #{$lockedInv->id}.",
                subject: $membership,
                request: $request,
            );
        });

        return back()->with('success', __('messages.demo.flash.invitation_link_rejected'));
    }

    public function storeDepartmentMembership(Request $request, SchoolMembership $schoolMembership): RedirectResponse
    {
        $scope = HierarchyScopeContext::resolveForRequest($request);
        abort_unless(
            $scope->canManageDepartmentMemberships($schoolMembership),
            403,
            'Only the assigned local Sunday School lead can assign or modify department participations for this school.'
        );

        $validated = $request->validate([
            'organizational_unit_id' => [
                'required',
                'integer',
                Rule::exists('organizational_units', 'id')
                    ->where('organization_id', $schoolMembership->organization_id)
                    ->where('unit_kind', OrganizationalUnit::KIND_DEPARTMENT),
            ],
            'role_label_am' => ['nullable', 'string', 'max:255'],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['nullable', 'date'],
        ]);

        $startsOn = (string) $validated['starts_on'];
        $endsOn = isset($validated['ends_on']) && trim((string) $validated['ends_on']) !== ''
            ? trim((string) $validated['ends_on'])
            : null;

        if ($endsOn !== null && strtotime($endsOn) < strtotime($startsOn)) {
            throw ValidationException::withMessages([
                'ends_on' => [__('messages.demo.department_membership.error_end_before_start', ['start' => $startsOn])],
            ]);
        }

        if ($endsOn !== null && strtotime($endsOn) < strtotime(now()->toDateString())) {
            throw ValidationException::withMessages([
                'ends_on' => [__('messages.demo.department_membership.error_active_end_in_past')],
            ]);
        }

        $actor = $request->user();

        try {
            DB::transaction(function () use ($request, $schoolMembership, $validated, $startsOn, $endsOn, $actor): void {
                // Serialize all department participation inserts/updates for this member by locking the parent SchoolMembership first
                $lockedMembership = SchoolMembership::query()
                    ->whereKey($schoolMembership->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (! in_array($lockedMembership->status, [
                    SchoolMembership::STATUS_APPROVED,
                    SchoolMembership::STATUS_PENDING,
                ], true)) {
                    throw ValidationException::withMessages([
                        'organizational_unit_id' => [__('messages.demo.department_membership.error_member_not_active_status')],
                    ]);
                }

                $alreadyActive = DepartmentMembership::query()
                    ->where('school_membership_id', $lockedMembership->id)
                    ->where('organizational_unit_id', $validated['organizational_unit_id'])
                    ->where('is_active', true)
                    ->lockForUpdate()
                    ->exists();

                if ($alreadyActive) {
                    throw ValidationException::withMessages([
                        'organizational_unit_id' => [__('messages.demo.department_membership.already_active')],
                    ]);
                }

                $dm = DepartmentMembership::query()->create([
                    'school_membership_id' => $lockedMembership->id,
                    'organizational_unit_id' => $validated['organizational_unit_id'],
                    'role_label_am' => ! empty($validated['role_label_am']) ? $validated['role_label_am'] : 'አባል',
                    'starts_on' => $startsOn,
                    'ends_on' => $endsOn,
                    'is_active' => true,
                    'assigned_by_user_id' => $actor?->id,
                ]);

                ActivityLogger::record(
                    actor: $actor,
                    event: 'department-memberships.assigned',
                    description: "Assigned member #{$lockedMembership->id} to department #{$dm->organizational_unit_id}.",
                    subject: $dm,
                    request: $request,
                );
            });
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages([
                'organizational_unit_id' => [__('messages.demo.department_membership.already_active')],
            ]);
        }

        return back()->with('success', __('messages.demo.flash.department_membership_assigned'));
    }

    public function updateDepartmentMembership(Request $request, DepartmentMembership $departmentMembership): RedirectResponse
    {
        $scope = HierarchyScopeContext::resolveForRequest($request);
        $departmentMembership->loadMissing('schoolMembership');

        abort_unless(
            $departmentMembership->schoolMembership !== null
            && $scope->canManageDepartmentMemberships($departmentMembership->schoolMembership),
            403,
            'Only the assigned local Sunday School lead can update or end department participations for this school.'
        );

        $validated = $request->validate([
            'action' => ['required', 'string', Rule::in(['end', 'reactivate', 'update'])],
            'role_label_am' => ['nullable', 'string', 'max:255'],
            'starts_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date'],
        ]);

        $actor = $request->user();
        $action = $validated['action'];

        try {
            DB::transaction(function () use ($request, $departmentMembership, $validated, $actor, $action): void {
                // Lock order: 1. Parent SchoolMembership -> 2. DepartmentMembership
                $lockedMembership = SchoolMembership::query()
                    ->whereKey($departmentMembership->school_membership_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $lockedDm = DepartmentMembership::query()
                    ->whereKey($departmentMembership->id)
                    ->where('school_membership_id', $lockedMembership->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($action === 'end') {
                    // Idempotent if already ended
                    if (! $lockedDm->is_active) {
                        return;
                    }

                    $endsOn = isset($validated['ends_on']) && trim((string) $validated['ends_on']) !== ''
                        ? trim((string) $validated['ends_on'])
                        : now()->toDateString();
                    $startsOn = $lockedDm->starts_on?->toDateString();

                    if ($startsOn !== null && strtotime($endsOn) < strtotime($startsOn)) {
                        throw ValidationException::withMessages([
                            'ends_on' => [__('messages.demo.department_membership.error_end_before_start', ['start' => $startsOn])],
                        ]);
                    }

                    $lockedDm->update([
                        'is_active' => false,
                        'ends_on' => $endsOn,
                        'ended_by_user_id' => $actor?->id,
                    ]);

                    ActivityLogger::record(
                        actor: $actor,
                        event: 'department-memberships.updated',
                        description: "Ended department participation #{$lockedDm->id} on {$endsOn}.",
                        subject: $lockedDm,
                        request: $request,
                    );

                    return;
                }

                if ($action === 'reactivate') {
                    if (! in_array($lockedMembership->status, [
                        SchoolMembership::STATUS_APPROVED,
                        SchoolMembership::STATUS_PENDING,
                    ], true)) {
                        throw ValidationException::withMessages([
                            'department_membership' => [__('messages.demo.department_membership.error_member_not_active_status')],
                        ]);
                    }

                    $otherActiveExists = DepartmentMembership::query()
                        ->where('school_membership_id', $lockedMembership->id)
                        ->where('organizational_unit_id', $lockedDm->organizational_unit_id)
                        ->where('is_active', true)
                        ->lockForUpdate()
                        ->exists();

                    if ($otherActiveExists) {
                        throw ValidationException::withMessages([
                            'department_membership' => [__('messages.demo.department_membership.already_active')],
                        ]);
                    }

                    $newStartsOn = isset($validated['starts_on']) && trim((string) $validated['starts_on']) !== ''
                        ? trim((string) $validated['starts_on'])
                        : now()->toDateString();
                    $newEndsOn = isset($validated['ends_on']) && trim((string) $validated['ends_on']) !== ''
                        ? trim((string) $validated['ends_on'])
                        : null;

                    $previousEndsOn = $lockedDm->ends_on?->toDateString();
                    if ($previousEndsOn !== null && strtotime($newStartsOn) < strtotime($previousEndsOn)) {
                        throw ValidationException::withMessages([
                            'starts_on' => [__('messages.demo.department_membership.error_new_period_before_previous_end', ['end' => $previousEndsOn])],
                        ]);
                    }

                    if ($newEndsOn !== null && strtotime($newEndsOn) < strtotime($newStartsOn)) {
                        throw ValidationException::withMessages([
                            'ends_on' => [__('messages.demo.department_membership.error_end_before_start', ['start' => $newStartsOn])],
                        ]);
                    }

                    if ($newEndsOn !== null && strtotime($newEndsOn) < strtotime(now()->toDateString())) {
                        throw ValidationException::withMessages([
                            'ends_on' => [__('messages.demo.department_membership.error_active_end_in_past')],
                        ]);
                    }

                    // Preserve the ended historical row intact and create a new dated interval
                    $newDm = DepartmentMembership::query()->create([
                        'school_membership_id' => $lockedMembership->id,
                        'organizational_unit_id' => $lockedDm->organizational_unit_id,
                        'role_label_am' => isset($validated['role_label_am']) && trim((string) $validated['role_label_am']) !== ''
                            ? trim((string) $validated['role_label_am'])
                            : $lockedDm->role_label_am,
                        'starts_on' => $newStartsOn,
                        'ends_on' => $newEndsOn,
                        'is_active' => true,
                        'assigned_by_user_id' => $actor?->id,
                        'ended_by_user_id' => null,
                    ]);

                    ActivityLogger::record(
                        actor: $actor,
                        event: 'department-memberships.reassigned',
                        description: "Created new department participation period #{$newDm->id} following ended period #{$lockedDm->id}.",
                        subject: $newDm,
                        properties: [
                            'previous_department_membership_id' => $lockedDm->id,
                            'new_department_membership_id' => $newDm->id,
                            'school_membership_id' => $lockedMembership->id,
                            'organizational_unit_id' => $lockedDm->organizational_unit_id,
                        ],
                        request: $request,
                    );

                    return;
                }

                // action === 'update'
                $startsOn = isset($validated['starts_on']) && trim((string) $validated['starts_on']) !== ''
                    ? trim((string) $validated['starts_on'])
                    : $lockedDm->starts_on?->toDateString();
                $endsOn = array_key_exists('ends_on', $validated)
                    ? (trim((string) $validated['ends_on']) !== '' ? trim((string) $validated['ends_on']) : null)
                    : $lockedDm->ends_on?->toDateString();

                if ($startsOn === null) {
                    throw ValidationException::withMessages([
                        'starts_on' => [__('messages.demo.department_membership.error_start_required')],
                    ]);
                }

                if (! $lockedDm->is_active && $endsOn === null) {
                    throw ValidationException::withMessages([
                        'ends_on' => [__('messages.demo.department_membership.error_ended_requires_end_date')],
                    ]);
                }

                if ($endsOn !== null && strtotime($endsOn) < strtotime($startsOn)) {
                    throw ValidationException::withMessages([
                        'ends_on' => [__('messages.demo.department_membership.error_end_before_start', ['start' => $startsOn])],
                    ]);
                }

                if ($lockedDm->is_active && $endsOn !== null && strtotime($endsOn) < strtotime(now()->toDateString())) {
                    throw ValidationException::withMessages([
                        'ends_on' => [__('messages.demo.department_membership.error_active_end_in_past')],
                    ]);
                }

                $lockedDm->update([
                    'role_label_am' => isset($validated['role_label_am']) && trim((string) $validated['role_label_am']) !== ''
                        ? trim((string) $validated['role_label_am'])
                        : $lockedDm->role_label_am,
                    'starts_on' => $startsOn,
                    'ends_on' => $endsOn,
                ]);

                ActivityLogger::record(
                    actor: $actor,
                    event: 'department-memberships.updated',
                    description: "Updated department participation #{$lockedDm->id} (update).",
                    subject: $lockedDm,
                    request: $request,
                );
            });
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages([
                'department_membership' => [__('messages.demo.department_membership.already_active')],
            ]);
        }

        return back()->with('success', __('messages.demo.flash.department_membership_updated'));
    }

    public function reportsIndex(Request $request): Response
    {
        $scope = HierarchyScopeContext::resolveForRequest($request);
        abort_unless($scope->canViewReportsMenu(), 403, 'Your active scope does not have access to departmental or upward reports.');

        $allReports = DemoReport::query()
            ->with(['organization.parent.parent', 'organizationalUnit', 'submittedBy:id,name', 'approvedBy:id,name'])
            ->orderByDesc('id')
            ->get();

        $visibleReports = $allReports
            ->filter(fn (DemoReport $report): bool => $scope->canViewReport($report))
            ->map(fn (DemoReport $report): array => $this->formatReportSummary($report, $scope))
            ->values()
            ->all();

        $completeness = [];
        if (
            $scope->isDistrictOfficer()
            || $scope->isDioceseOfficer()
            || $scope->isUnionDivisionCoordinator()
            || $scope->isUnionBoardMember()
            || $scope->isUnionSuperAdmin()
        ) {
            $root = $scope->appointment?->organization;
            if ($root !== null) {
                $schools = Organization::query()
                    ->where('type', Organization::TYPE_SUNDAY_SCHOOL)
                    ->whereIn('id', $root->subtreeIds())
                    ->with('parent')
                    ->orderBy('id')
                    ->get();

                foreach ($schools as $school) {
                    $releasedCount = $allReports
                        ->where('organization_id', $school->id)
                        ->where('status', DemoReport::STATUS_RELEASED)
                        ->filter(fn (DemoReport $r): bool => $scope->canViewReport($r))
                        ->count();

                    $completeness[] = [
                        'schoolId' => $school->id,
                        'schoolNameAm' => $school->name_am,
                        'schoolNameEn' => $school->name_en,
                        'districtNameAm' => $school->parent?->name_am,
                        'districtNameEn' => $school->parent?->name_en,
                        'cityAm' => $school->city_am,
                        'cityEn' => $school->city_en,
                        'releasedReportsCount' => $releasedCount,
                        'completenessState' => $releasedCount > 0 ? 'submitted_released' : 'missing_submission',
                    ];
                }
            }
        }

        $nationalSummary = null;
        if ($scope->hasCapability('reports.view_national_summary')) {
            $reportingSchoolsCount = collect($completeness)
                ->where('completenessState', 'submitted_released')
                ->count();
            $missingSchoolsCount = collect($completeness)
                ->where('completenessState', 'missing_submission')
                ->count();

            $nationalSummary = [
                'diocesesCount' => Organization::query()->where('type', Organization::TYPE_DIOCESE)->count(),
                'districtsCount' => Organization::query()->where('type', Organization::TYPE_DISTRICT)->count(),
                'schoolsCount' => Organization::query()->where('type', Organization::TYPE_SUNDAY_SCHOOL)->count(),
                'approvedMembersTotal' => SchoolMembership::query()
                    ->where('status', SchoolMembership::STATUS_APPROVED)
                    ->count(),
                'releasedReportsTotal' => DemoReport::query()
                    ->where('status', DemoReport::STATUS_RELEASED)
                    ->count(),
                'reportingSchoolsCount' => $reportingSchoolsCount,
                'missingSchoolsCount' => $missingSchoolsCount,
            ];
        }

        return Inertia::render('reports/Index', [
            'reports' => $visibleReports,
            'completeness' => $completeness,
            'nationalSummary' => $nationalSummary,
            'scopeSummary' => $scope->toInertiaPayload(),
        ]);
    }

    public function showReport(Request $request, DemoReport $demoReport): Response
    {
        $scope = HierarchyScopeContext::resolveForRequest($request);
        $demoReport->load(['organization.parent.parent', 'organizationalUnit', 'submittedBy:id,name', 'approvedBy:id,name']);

        abort_unless($scope->canViewReport($demoReport), 403, 'Access denied: this report is either an unapproved local draft or outside your organizational/functional jurisdiction.');

        return Inertia::render('reports/Show', [
            'report' => [
                ...$this->formatReportSummary($demoReport, $scope),
                'summaryAm' => $demoReport->summary_am,
                'districtNameAm' => $demoReport->organization?->parent?->name_am,
                'districtNameEn' => $demoReport->organization?->parent?->name_en,
                'dioceseNameAm' => $demoReport->organization?->parent?->parent?->name_am,
                'dioceseNameEn' => $demoReport->organization?->parent?->parent?->name_en,
            ],
            'scopeSummary' => $scope->toInertiaPayload(),
        ]);
    }

    public function submitReportLocally(Request $request, DemoReport $demoReport): RedirectResponse
    {
        $scope = HierarchyScopeContext::resolveForRequest($request);
        abort_unless($scope->canSubmitDepartmentDraft($demoReport), 403, 'Only the assigned department coordinator can submit this draft report to the local Sunday School lead.');

        $demoReport->update([
            'status' => DemoReport::STATUS_SUBMITTED_LOCAL,
            'submitted_by_user_id' => $request->user()?->id,
        ]);

        ActivityLogger::record(
            actor: $request->user(),
            event: 'reports.submitted-local',
            description: "Submitted department report #{$demoReport->id} to local Sunday School lead.",
            subject: $demoReport,
            request: $request,
        );

        return back()->with('success', __('messages.demo.flash.report_submitted_local'));
    }

    public function releaseReportUpward(Request $request, DemoReport $demoReport): RedirectResponse
    {
        $scope = HierarchyScopeContext::resolveForRequest($request);
        abort_unless($scope->canApproveAndReleaseReport($demoReport), 403, 'Only the local Sunday School lead can approve and release reports to upper coordinating offices.');

        DB::transaction(function () use ($request, $demoReport): void {
            $demoReport->update([
                'status' => DemoReport::STATUS_RELEASED,
                'approved_by_user_id' => $request->user()?->id,
                'released_at' => now(),
            ]);

            ActivityLogger::record(
                actor: $request->user(),
                event: 'reports.released-upward',
                description: "Local lead approved and released report #{$demoReport->id} upward.",
                subject: $demoReport,
                request: $request,
            );
        });

        return back()->with('success', __('messages.demo.flash.report_released_upward'));
    }

    public function showMyProfile(Request $request): Response
    {
        $scope = HierarchyScopeContext::resolveForRequest($request);
        $user = $request->user();

        $membership = $scope->membership
            ?? $user?->schoolMemberships()
                ->with(['person', 'organization.parent', 'departmentMemberships.organizationalUnit', 'decisions.actor:id,name'])
                ->first()
            ?? $user?->pendingSchoolMemberships()
                ->with(['person', 'organization.parent', 'departmentMemberships.organizationalUnit', 'decisions.actor:id,name'])
                ->first();

        if ($membership !== null) {
            $membership->loadMissing(['person', 'organization.parent', 'departmentMemberships.organizationalUnit', 'decisions.actor:id,name']);
        }

        $isPendingLinkOnly = $membership !== null
            && $user !== null
            && (int) $membership->user_id !== (int) $user->id
            && (int) $membership->pending_user_id === (int) $user->id;

        if ($membership === null && $user !== null) {
            $latestRejectedOrRevoked = $user->acceptedMembershipInvitations()
                ->with(['organization'])
                ->whereIn('status', [MembershipInvitation::STATUS_REJECTED, MembershipInvitation::STATUS_REVOKED])
                ->first();

            if ($latestRejectedOrRevoked !== null) {
                $ownDecisions = MembershipDecision::query()
                    ->with('actor:id,name')
                    ->where('school_membership_id', $latestRejectedOrRevoked->school_membership_id)
                    ->where('target_user_id', $user->id)
                    ->orderByDesc('id')
                    ->get()
                    ->map(fn (MembershipDecision $d): array => [
                        'id' => $d->id,
                        'action' => $d->action,
                        'fromStatus' => $d->from_status,
                        'toStatus' => $d->to_status,
                        'reason' => $d->reason,
                        'actorName' => $d->actor?->name,
                        'createdAt' => $d->created_at?->toDateTimeString(),
                    ])
                    ->values()
                    ->all();

                return Inertia::render('members/MyProfile', [
                    'membership' => [
                        'id' => $latestRejectedOrRevoked->school_membership_id,
                        'membershipCode' => null,
                        'status' => SchoolMembership::STATUS_REJECTED,
                        'rawStatus' => SchoolMembership::STATUS_REJECTED,
                        'accountLinkStatus' => $latestRejectedOrRevoked->status,
                        'isPendingLinkOnly' => false,
                        'statusReason' => $latestRejectedOrRevoked->review_note,
                        'schoolNameAm' => $latestRejectedOrRevoked->organization?->name_am,
                        'schoolNameEn' => $latestRejectedOrRevoked->organization?->name_en,
                        'parishNameAm' => $latestRejectedOrRevoked->organization?->parish_name_am,
                        'parishNameEn' => $latestRejectedOrRevoked->organization?->parish_name_en,
                        'cityAm' => $latestRejectedOrRevoked->organization?->city_am,
                        'cityEn' => $latestRejectedOrRevoked->organization?->city_en,
                        'applicationNote' => null,
                        'joinedOn' => null,
                        'endedOn' => null,
                        'approvedAt' => null,
                        'fullName' => $user->name,
                        'nameEn' => '',
                        'phone' => '',
                        'serviceNotes' => '',
                        'canEditOwnProfile' => false,
                        'departments' => [],
                        'decisions' => $ownDecisions,
                    ],
                    'scopeSummary' => $scope->toInertiaPayload(),
                ]);
            }
        }

        $effectiveStatus = $isPendingLinkOnly
            ? SchoolMembership::STATUS_PENDING
            : ($membership?->status ?? 'unlinked');

        return Inertia::render('members/MyProfile', [
            'membership' => $membership === null ? null : [
                'id' => $membership->id,
                'membershipCode' => $isPendingLinkOnly ? null : $membership->membership_code,
                'status' => $effectiveStatus,
                'rawStatus' => $membership->status,
                'accountLinkStatus' => $membership->account_link_status,
                'isPendingLinkOnly' => $isPendingLinkOnly,
                'statusReason' => $isPendingLinkOnly ? null : $membership->status_reason,
                'schoolNameAm' => $membership->organization?->name_am,
                'schoolNameEn' => $membership->organization?->name_en,
                'parishNameAm' => $membership->organization?->parish_name_am,
                'parishNameEn' => $membership->organization?->parish_name_en,
                'cityAm' => $membership->organization?->city_am,
                'cityEn' => $membership->organization?->city_en,
                'applicationNote' => $isPendingLinkOnly ? null : $membership->application_note,
                'joinedOn' => $isPendingLinkOnly ? null : $membership->joined_on?->toDateString(),
                'endedOn' => $isPendingLinkOnly ? null : $membership->ended_on?->toDateString(),
                'approvedAt' => $isPendingLinkOnly ? null : $membership->approved_at?->toDateTimeString(),
                'fullName' => $isPendingLinkOnly
                    ? ($user?->name ?? '')
                    : ($membership->person?->full_name ?? $user?->name ?? ''),
                'nameEn' => $isPendingLinkOnly ? '' : ($membership->person?->name_en ?? ''),
                'phone' => $isPendingLinkOnly ? '' : ($membership->person?->phone ?? ''),
                'serviceNotes' => $isPendingLinkOnly ? '' : ($membership->person?->service_notes ?? ''),
                'canEditOwnProfile' => ! $isPendingLinkOnly
                    && $scope->isApprovedMember()
                    && $membership->status === SchoolMembership::STATUS_APPROVED,
                'departments' => ! $isPendingLinkOnly && $membership->status === SchoolMembership::STATUS_APPROVED
                    ? $membership->departmentMemberships
                        ->filter(fn (DepartmentMembership $dm): bool => $dm->isCurrentlyEffective())
                        ->map(fn (DepartmentMembership $dm): array => [
                            'id' => $dm->organizational_unit_id,
                            'nameAm' => $dm->organizationalUnit?->name_am ?? '',
                            'nameEn' => $dm->organizationalUnit?->name_en,
                            'roleLabelAm' => $dm->role_label_am,
                        ])
                        ->values()
                        ->all()
                    : [],
                'decisions' => $membership->decisions
                    ->filter(fn (MembershipDecision $d): bool => ! $isPendingLinkOnly || (int) $d->target_user_id === (int) $user?->id)
                    ->map(fn (MembershipDecision $d): array => [
                        'id' => $d->id,
                        'action' => $d->action,
                        'fromStatus' => $d->from_status,
                        'toStatus' => $d->to_status,
                        'reason' => $d->reason,
                        'actorName' => $d->actor?->name,
                        'createdAt' => $d->created_at?->toDateTimeString(),
                    ])
                    ->values()
                    ->all(),
            ],
            'scopeSummary' => $scope->toInertiaPayload(),
        ]);
    }

    public function updateMyProfile(Request $request): RedirectResponse
    {
        $scope = HierarchyScopeContext::resolveForRequest($request);
        $membership = $scope->membership;

        abort_unless(
            $scope->isApprovedMember()
            && $membership !== null
            && (int) $membership->user_id === (int) $request->user()?->id
            && $membership->status === SchoolMembership::STATUS_APPROVED
            && $membership->person !== null,
            403,
            'Only an approved Sunday School member can update their member profile record.'
        );

        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:64'],
            'service_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($request, $membership, $validated): void {
            /** @var SchoolMembership $lockedMembership */
            $lockedMembership = SchoolMembership::query()
                ->whereKey($membership->id)
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless(
                (int) $lockedMembership->user_id === (int) $request->user()?->id
                && $lockedMembership->status === SchoolMembership::STATUS_APPROVED,
                403,
                'Only an approved Sunday School member can update their member profile record.'
            );

            $person = Person::query()
                ->whereKey($lockedMembership->person_id)
                ->lockForUpdate()
                ->firstOrFail();

            $sharedWithOtherSchool = SchoolMembership::query()
                ->where('person_id', $person->id)
                ->where('organization_id', '!=', $lockedMembership->organization_id)
                ->exists();

            if ($sharedWithOtherSchool) {
                throw ValidationException::withMessages([
                    'full_name' => [__('messages.demo.member_form.shared_person_edit_deferred')],
                ]);
            }

            // Preserve Amharic, English, and mixed-script text verbatim in PostgreSQL UTF-8.
            $person->update([
                'full_name' => $validated['full_name'],
                'name_en' => $validated['name_en'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'service_notes' => $validated['service_notes'] ?? null,
            ]);

            $request->user()?->update([
                'name' => $validated['full_name'],
            ]);

            ActivityLogger::record(
                actor: $request->user(),
                event: 'member-profile.updated',
                description: "Member updated own permitted profile fields (membership #{$lockedMembership->id}).",
                subject: $person,
                request: $request,
            );
        });

        return to_route('my-profile.show')->with('success', __('messages.demo.flash.profile_updated'));
    }

    /**
     * @return array{invitation: MembershipInvitation, rawToken: string}
     */
    private function issueInvitationForMembership(
        Organization $school,
        SchoolMembership $membership,
        string $invitedEmail,
        ?User $actor,
        Request $request,
    ): array {
        // Revoke any previous unaccepted invitations for this membership before issuing a new one.
        MembershipInvitation::query()
            ->where('school_membership_id', $membership->id)
            ->whereIn('status', [MembershipInvitation::STATUS_PENDING_ACCEPTANCE, MembershipInvitation::STATUS_EXPIRED])
            ->lockForUpdate()
            ->update([
                'status' => MembershipInvitation::STATUS_REVOKED,
                'revoked_at' => now(),
                'revoked_by_user_id' => $actor?->id,
                'review_note' => __('messages.demo.decisions.default_invitation_revoked_note'),
            ]);

        $rawToken = Str::random(48);
        $tokenHash = hash('sha256', $rawToken);

        $invitation = MembershipInvitation::query()->create([
            'organization_id' => $school->id,
            'school_membership_id' => $membership->id,
            'token_hash' => $tokenHash,
            'invited_email' => mb_strtolower(trim($invitedEmail)),
            'invited_name' => $membership->person?->full_name,
            'status' => MembershipInvitation::STATUS_PENDING_ACCEPTANCE,
            'mail_delivery_status' => MembershipInvitation::MAIL_PENDING,
            'mail_sent_at' => null,
            'mail_error' => null,
            'created_by_user_id' => $actor?->id,
            'expires_at' => now()->addDays(7),
        ]);

        $membership->update([
            'account_link_status' => SchoolMembership::LINK_PENDING_ACCEPTANCE,
        ]);

        MembershipDecision::query()->create([
            'school_membership_id' => $membership->id,
            'organization_id' => $school->id,
            'actor_user_id' => $actor?->id,
            'target_user_id' => null,
            'action' => MembershipDecision::ACTION_INVITATION_SENT,
            'from_status' => $membership->status,
            'to_status' => $membership->status,
            'reason' => "Invitation issued to {$invitation->invited_email} (expires {$invitation->expires_at?->toDateString()}).",
        ]);

        ActivityLogger::record(
            actor: $actor,
            event: 'invitations.created',
            description: "Created membership invitation #{$invitation->id} for membership #{$membership->id} in school {$school->code}.",
            subject: $invitation,
            properties: [
                'invitation_id' => $invitation->id,
                'school_membership_id' => $membership->id,
                'organization_id' => $school->id,
                'invited_email' => $invitation->invited_email,
                'expires_at' => $invitation->expires_at?->toISOString(),
            ],
            request: $request,
        );

        return [
            'invitation' => $invitation,
            'rawToken' => $rawToken,
        ];
    }

    private function deliverCommittedInvitationMail(
        MembershipInvitation $invitation,
        Organization $school,
        SchoolMembership $membership,
        string $rawToken,
        ?User $actor,
        Request $request,
    ): bool {
        $acceptanceUrl = route('invitations.show', ['token' => $rawToken]);

        try {
            Mail::to($invitation->invited_email)->send(new SchoolMembershipInvitationMail(
                invitation: $invitation,
                school: $school,
                membership: $membership,
                acceptanceUrl: $acceptanceUrl,
            ));

            $invitation->update([
                'mail_delivery_status' => MembershipInvitation::MAIL_SENT,
                'mail_sent_at' => now(),
                'mail_error' => null,
            ]);

            return true;
        } catch (\Throwable) {
            // Never log raw tokens, passwords, or internal transport exception details.
            $invitation->update([
                'mail_delivery_status' => MembershipInvitation::MAIL_FAILED,
                'mail_error' => 'mail_transport_failed',
            ]);

            ActivityLogger::record(
                actor: $actor,
                event: 'invitations.mail-failed',
                description: "Invitation mail delivery failed for invitation #{$invitation->id} in school {$school->code}.",
                subject: $invitation,
                properties: [
                    'invitation_id' => $invitation->id,
                    'school_membership_id' => $membership->id,
                    'organization_id' => $school->id,
                    'invited_email' => $invitation->invited_email,
                ],
                request: $request,
            );

            return false;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function formatReportSummary(DemoReport $report, HierarchyScopeContext $scope): array
    {
        return [
            'id' => $report->id,
            'schoolId' => $report->organization_id,
            'schoolNameAm' => $report->organization?->name_am ?? '',
            'schoolNameEn' => $report->organization?->name_en,
            'unitId' => $report->organizational_unit_id,
            'unitNameAm' => $report->organizationalUnit?->name_am,
            'unitNameEn' => $report->organizationalUnit?->name_en,
            'categoryCode' => $report->category_code,
            'periodLabel' => $report->period_label,
            'titleAm' => $report->title_am,
            'activityCount' => $report->activity_count,
            'status' => $report->status,
            'submittedBy' => $report->submittedBy?->name,
            'approvedBy' => $report->approvedBy?->name,
            'releasedAt' => $report->released_at?->toDateTimeString(),
            'canSubmitLocal' => $scope->canSubmitDepartmentDraft($report),
            'canReleaseUpward' => $scope->canApproveAndReleaseReport($report),
        ];
    }
}
