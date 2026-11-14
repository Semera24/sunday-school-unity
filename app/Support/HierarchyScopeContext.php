<?php

namespace App\Support;

use App\Models\DemoReport;
use App\Models\MembershipInvitation;
use App\Models\Organization;
use App\Models\OrganizationalUnit;
use App\Models\SchoolMembership;
use App\Models\ScopedAppointment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class HierarchyScopeContext
{
    public const SESSION_KEY = 'ssu_active_scope_key';

    /**
     * @param  Collection<int, array<string, mixed>>  $availableContexts
     * @param  array<string, mixed>|null  $activeContext
     */
    public function __construct(
        public readonly ?User $user,
        public readonly Collection $availableContexts,
        public readonly ?array $activeContext,
        public readonly ?ScopedAppointment $appointment,
        public readonly ?SchoolMembership $membership,
    ) {}

    public static function resolveForRequest(Request $request): self
    {
        $user = $request->user();

        if ($user === null) {
            return new self(
                user: null,
                availableContexts: collect(),
                activeContext: null,
                appointment: null,
                membership: null,
            );
        }

        $directMemberships = $user->schoolMemberships()
            ->with(['person', 'organization.parent', 'departmentMemberships.organizationalUnit'])
            ->orderBy('id')
            ->get();

        $pendingLinkMemberships = $user->pendingSchoolMemberships()
            ->with(['person', 'organization.parent', 'departmentMemberships.organizationalUnit'])
            ->orderBy('id')
            ->get();

        $memberships = $directMemberships
            ->concat($pendingLinkMemberships)
            ->unique('id')
            ->values();

        // Organizations where the user's membership has been suspended, archived, or rejected
        $blockedOrgIds = $directMemberships
            ->filter(fn (SchoolMembership $m): bool => in_array($m->status, [
                SchoolMembership::STATUS_SUSPENDED,
                SchoolMembership::STATUS_ARCHIVED,
                SchoolMembership::STATUS_REJECTED,
            ], true))
            ->pluck('organization_id')
            ->unique()
            ->all();

        $appointments = $user->scopedAppointments()
            ->effective()
            ->with(['organization.parent', 'organizationalUnit'])
            ->orderBy('id')
            ->get()
            ->reject(function (ScopedAppointment $appointment) use ($blockedOrgIds): bool {
                // Suspending, archiving, or rejecting a member blocks department coordinator authority in that school
                return $appointment->role_key === ScopedAppointment::ROLE_DEPARTMENT_COORDINATOR
                    && in_array($appointment->organization_id, $blockedOrgIds, true);
            })
            ->values();

        $contexts = collect();

        foreach ($appointments as $appointment) {
            $key = 'appointment:'.$appointment->id;
            $contexts->push([
                'key' => $key,
                'kind' => 'appointment',
                'id' => $appointment->id,
                'roleKey' => $appointment->role_key,
                'titleAm' => $appointment->title_am,
                'titleEn' => $appointment->title_en ?? $appointment->title_am,
                'organizationId' => $appointment->organization_id,
                'organizationNameAm' => $appointment->organization?->name_am ?? 'የመድረክ ቴክኒካል አስተዳደር',
                'organizationNameEn' => $appointment->organization?->name_en ?? 'Platform Technical Operations',
                'organizationType' => $appointment->organization?->type ?? 'platform',
                'unitId' => $appointment->organizational_unit_id,
                'unitNameAm' => $appointment->organizationalUnit?->name_am,
                'unitNameEn' => $appointment->organizationalUnit?->name_en,
                'functionalCategory' => $appointment->functional_category,
                'startsOn' => $appointment->starts_on?->toDateString(),
                'endsOn' => $appointment->ends_on?->toDateString(),
                'model' => $appointment,
            ]);
        }

        foreach ($memberships as $membership) {
            $key = 'membership:'.$membership->id;
            $isPendingLinkOnly = (int) $membership->user_id !== (int) $user->id
                && (int) $membership->pending_user_id === (int) $user->id;

            if ($isPendingLinkOnly) {
                $roleKey = 'pending_member';
                $titleAm = 'በመጠባበቅ ላይ ያለ የመለያ ትስስር ጥያቄ';
                $titleEn = 'Pending Account-Link Request';
            } else {
                [$roleKey, $titleAm, $titleEn] = match ($membership->status) {
                    SchoolMembership::STATUS_APPROVED => [
                        'approved_member',
                        'የጸደቀ የሰንበት ት/ቤት አባል',
                        'Approved Sunday School Member',
                    ],
                    SchoolMembership::STATUS_REJECTED => [
                        'rejected_member',
                        'ውድቅ የተደረገ የአባልነት ጥያቄ',
                        'Rejected Membership Request',
                    ],
                    SchoolMembership::STATUS_SUSPENDED => [
                        'suspended_member',
                        'ለጊዜው የታገደ የሰንበት ት/ቤት አባልነት',
                        'Suspended Sunday School Membership',
                    ],
                    SchoolMembership::STATUS_ARCHIVED => [
                        'archived_member',
                        'በማኅደር የተቀመጠ የቀድሞ አባልነት',
                        'Archived Sunday School Membership',
                    ],
                    default => [
                        'pending_member',
                        'በመጠባበቅ ላይ ያለ የአባልነት ጥያቄ',
                        'Pending Membership Request',
                    ],
                };
            }

            $contexts->push([
                'key' => $key,
                'kind' => 'membership',
                'id' => $membership->id,
                'roleKey' => $roleKey,
                'titleAm' => $titleAm,
                'titleEn' => $titleEn,
                'organizationId' => $membership->organization_id,
                'organizationNameAm' => $membership->organization?->name_am ?? '',
                'organizationNameEn' => $membership->organization?->name_en ?? '',
                'organizationType' => $membership->organization?->type ?? Organization::TYPE_SUNDAY_SCHOOL,
                'unitId' => null,
                'unitNameAm' => null,
                'unitNameEn' => null,
                'functionalCategory' => null,
                'startsOn' => $membership->joined_on?->toDateString(),
                'endsOn' => $membership->ended_on?->toDateString(),
                'model' => $membership,
            ]);
        }

        if ($contexts->isEmpty()) {
            $latestInvitation = $user->acceptedMembershipInvitations()
                ->with('organization')
                ->whereIn('status', [
                    MembershipInvitation::STATUS_REJECTED,
                    MembershipInvitation::STATUS_REVOKED,
                ])
                ->orderByDesc('id')
                ->first();

            if ($latestInvitation !== null) {
                $contexts->push([
                    'key' => 'invitation:'.$latestInvitation->id,
                    'kind' => 'invitation',
                    'id' => $latestInvitation->id,
                    'roleKey' => 'rejected_member',
                    'titleAm' => 'ውድቅ የተደረገ የመለያ ትስስር ጥያቄ',
                    'titleEn' => 'Rejected Account-Link Request',
                    'organizationId' => $latestInvitation->organization_id,
                    'organizationNameAm' => $latestInvitation->organization?->name_am ?? '',
                    'organizationNameEn' => $latestInvitation->organization?->name_en ?? '',
                    'organizationType' => $latestInvitation->organization?->type ?? Organization::TYPE_SUNDAY_SCHOOL,
                    'unitId' => null,
                    'unitNameAm' => null,
                    'unitNameEn' => null,
                    'functionalCategory' => null,
                    'startsOn' => $latestInvitation->accepted_at?->toDateString(),
                    'endsOn' => ($latestInvitation->reviewed_at ?? $latestInvitation->revoked_at)?->toDateString(),
                    'model' => null,
                ]);
            }
        }

        $requestedKey = $request->hasSession() ? $request->session()->get(self::SESSION_KEY) : null;
        $active = $requestedKey !== null
            ? $contexts->firstWhere('key', $requestedKey)
            : null;

        if ($active === null) {
            $active = $contexts->first();
        }

        return new self(
            user: $user,
            availableContexts: $contexts,
            activeContext: $active,
            appointment: ($active['kind'] ?? null) === 'appointment' ? $active['model'] : null,
            membership: ($active['kind'] ?? null) === 'membership' ? $active['model'] : null,
        );
    }

    public function roleKey(): string
    {
        return $this->activeContext['roleKey'] ?? 'unlinked';
    }

    /**
     * Stable internal permission codes granted by the active scoped context.
     *
     * @return list<string>
     */
    public function permissionCodes(): array
    {
        return match ($this->roleKey()) {
            ScopedAppointment::ROLE_UNION_SUPER_ADMIN => [
                'hierarchy.view_nationwide',
                'organizations.create_nationwide',
                'organizations.update_nationwide',
                'appointments.manage_local_leads',
                'reports.view_released_nationwide',
                'reports.view_completeness_nationwide',
                'reports.view_national_summary',
            ],
            ScopedAppointment::ROLE_SCHOOL_LEAD => [
                'hierarchy.view_directory',
                'school.manage_own',
                'departments.manage_own_school',
                'department_memberships.manage_own_school',
                'appointments.manage_local_coordinators',
                'memberships.view_own_school',
                'memberships.create_own_school',
                'memberships.update_own_school',
                'memberships.approve_own_school',
                'memberships.manage_lifecycle_own_school',
                'invitations.manage_own_school',
                'reports.view_own_school',
                'reports.release_own_school',
            ],
            ScopedAppointment::ROLE_DEPARTMENT_COORDINATOR => [
                'hierarchy.view_directory',
                'department.view_assigned',
                'memberships.view_assigned_department',
                'reports.view_assigned_department',
                'reports.submit_local_draft',
            ],
            ScopedAppointment::ROLE_DIOCESE_OFFICER => [
                'hierarchy.view_directory',
                'organizations.create_jurisdiction',
                'reports.view_released_jurisdiction',
                'reports.view_completeness_jurisdiction',
            ],
            ScopedAppointment::ROLE_DISTRICT_OFFICER => [
                'hierarchy.view_directory',
                'reports.view_released_jurisdiction',
                'reports.view_completeness_jurisdiction',
            ],
            ScopedAppointment::ROLE_UNION_DIVISION_COORDINATOR => [
                'hierarchy.view_directory',
                'reports.view_released_division',
                'reports.view_completeness_nationwide',
            ],
            ScopedAppointment::ROLE_UNION_BOARD_MEMBER => [
                'hierarchy.view_directory',
                'reports.view_released_nationwide',
                'reports.view_completeness_nationwide',
            ],
            ScopedAppointment::ROLE_TECHNICAL_OPERATOR => [
                'hierarchy.view_structural',
                'platform.technical_admin',
            ],
            'approved_member' => [
                'member.profile.self_view',
                'member.profile.self_update',
            ],
            'pending_member', 'rejected_member', 'suspended_member', 'archived_member', 'unlinked' => [
                'member.status.self_view',
            ],
            default => [],
        };
    }

    public function hasCapability(string $code): bool
    {
        return in_array($code, $this->permissionCodes(), true);
    }

    public function isUnionSuperAdmin(): bool
    {
        return $this->roleKey() === ScopedAppointment::ROLE_UNION_SUPER_ADMIN;
    }

    public function isTechnicalOperator(): bool
    {
        return $this->roleKey() === ScopedAppointment::ROLE_TECHNICAL_OPERATOR;
    }

    public function isSchoolLead(): bool
    {
        return $this->roleKey() === ScopedAppointment::ROLE_SCHOOL_LEAD;
    }

    public function isDepartmentCoordinator(): bool
    {
        return $this->roleKey() === ScopedAppointment::ROLE_DEPARTMENT_COORDINATOR;
    }

    public function isDistrictOfficer(): bool
    {
        return $this->roleKey() === ScopedAppointment::ROLE_DISTRICT_OFFICER;
    }

    public function isDioceseOfficer(): bool
    {
        return $this->roleKey() === ScopedAppointment::ROLE_DIOCESE_OFFICER;
    }

    public function isUnionDivisionCoordinator(): bool
    {
        return $this->roleKey() === ScopedAppointment::ROLE_UNION_DIVISION_COORDINATOR;
    }

    public function isUnionBoardMember(): bool
    {
        return $this->roleKey() === ScopedAppointment::ROLE_UNION_BOARD_MEMBER;
    }

    public function isApprovedMember(): bool
    {
        return $this->roleKey() === 'approved_member';
    }

    public function isPendingOrUnlinked(): bool
    {
        return in_array($this->roleKey(), [
            'pending_member',
            'rejected_member',
            'suspended_member',
            'archived_member',
            'unlinked',
        ], true);
    }

    public function canViewHierarchyExplorer(): bool
    {
        return ! $this->isPendingOrUnlinked() && ! $this->isApprovedMember();
    }

    public function canViewReportsMenu(): bool
    {
        return in_array($this->roleKey(), [
            ScopedAppointment::ROLE_UNION_SUPER_ADMIN,
            ScopedAppointment::ROLE_SCHOOL_LEAD,
            ScopedAppointment::ROLE_DEPARTMENT_COORDINATOR,
            ScopedAppointment::ROLE_DISTRICT_OFFICER,
            ScopedAppointment::ROLE_DIOCESE_OFFICER,
            ScopedAppointment::ROLE_UNION_DIVISION_COORDINATOR,
            ScopedAppointment::ROLE_UNION_BOARD_MEMBER,
        ], true);
    }

    public function canViewSchoolWorkspace(Organization $school): bool
    {
        if ($school->type !== Organization::TYPE_SUNDAY_SCHOOL) {
            return false;
        }

        if ($this->isSchoolLead() || $this->isDepartmentCoordinator()) {
            return (int) $this->appointment?->organization_id === (int) $school->id;
        }

        if ($this->isUnionSuperAdmin() || $this->isDistrictOfficer() || $this->isDioceseOfficer() || $this->isUnionDivisionCoordinator() || $this->isUnionBoardMember()) {
            $root = $this->appointment?->organization;

            return $root !== null && $school->isWithinJurisdictionOf($root);
        }

        return false;
    }

    public function canManageSchoolLocally(Organization $school): bool
    {
        return $this->isSchoolLead()
            && $school->type === Organization::TYPE_SUNDAY_SCHOOL
            && (int) $this->appointment?->organization_id === (int) $school->id;
    }

    public function canViewSchoolMembersDirectory(Organization $school): bool
    {
        // Only the school's own local lead can view its full private member directory.
        // Union Super Administrator and higher offices do not automatically receive raw member directory access.
        return $this->canManageSchoolLocally($school);
    }

    public function canCreateMemberInSchool(Organization $school): bool
    {
        return $this->canManageSchoolLocally($school);
    }

    public function canUpdateMemberRecord(SchoolMembership $targetMembership): bool
    {
        return $this->isSchoolLead()
            && (int) $this->appointment?->organization_id === (int) $targetMembership->organization_id;
    }

    public function canManageMembershipLifecycle(SchoolMembership $targetMembership): bool
    {
        return $this->isSchoolLead()
            && (int) $this->appointment?->organization_id === (int) $targetMembership->organization_id;
    }

    public function canManageSchoolInvitations(Organization $school): bool
    {
        return $this->canManageSchoolLocally($school);
    }

    public function canManageDepartmentMemberships(SchoolMembership $targetMembership): bool
    {
        return $this->isSchoolLead()
            && (int) $this->appointment?->organization_id === (int) $targetMembership->organization_id;
    }

    public function canViewDepartmentWorkspace(OrganizationalUnit $unit): bool
    {
        if ($unit->unit_kind !== OrganizationalUnit::KIND_DEPARTMENT) {
            return false;
        }

        if ($this->isSchoolLead()) {
            return (int) $this->appointment?->organization_id === (int) $unit->organization_id;
        }

        if ($this->isDepartmentCoordinator()) {
            return (int) $this->appointment?->organization_id === (int) $unit->organization_id
                && (int) $this->appointment?->organizational_unit_id === (int) $unit->id;
        }

        return false;
    }

    public function canViewMembershipRecord(SchoolMembership $targetMembership): bool
    {
        if (
            $this->membership !== null
            && (int) $this->membership->id === (int) $targetMembership->id
            && $this->isApprovedMember()
            && $targetMembership->status === SchoolMembership::STATUS_APPROVED
        ) {
            return true;
        }

        if ($this->isSchoolLead()) {
            return (int) $this->appointment?->organization_id === (int) $targetMembership->organization_id;
        }

        if ($this->isDepartmentCoordinator()) {
            $unitId = (int) $this->appointment?->organizational_unit_id;
            if ((int) $this->appointment?->organization_id !== (int) $targetMembership->organization_id) {
                return false;
            }

            if ($targetMembership->status !== SchoolMembership::STATUS_APPROVED) {
                return false;
            }

            return $targetMembership->departmentMemberships()
                ->currentlyEffective()
                ->where('organizational_unit_id', $unitId)
                ->exists();
        }

        return false;
    }

    public function canApproveMembership(SchoolMembership $targetMembership): bool
    {
        return $this->isSchoolLead()
            && (int) $this->appointment?->organization_id === (int) $targetMembership->organization_id;
    }

    /**
     * Allowed report categories for a Union Division Coordinator.
     *
     * @return list<string>
     */
    public function allowedDivisionReportCategories(): array
    {
        $category = $this->appointment?->functional_category;

        return match ($category) {
            'reporting_coordination' => ['membership', 'hymns', 'development', 'reporting_coordination'],
            default => $category !== null ? [$category] : [],
        };
    }

    public function canViewReport(DemoReport $report): bool
    {
        if ($this->isSchoolLead()) {
            return (int) $this->appointment?->organization_id === (int) $report->organization_id;
        }

        if ($this->isDepartmentCoordinator()) {
            return (int) $this->appointment?->organization_id === (int) $report->organization_id
                && (int) $this->appointment?->organizational_unit_id === (int) $report->organizational_unit_id;
        }

        // Union Super Admin, upper offices, union divisions and union board can ONLY view released reports!
        if ($report->status !== DemoReport::STATUS_RELEASED) {
            return false;
        }

        if ($this->isUnionSuperAdmin() || $this->isDistrictOfficer() || $this->isDioceseOfficer() || $this->isUnionBoardMember()) {
            $root = $this->appointment?->organization;
            $school = $report->organization;

            return $root !== null && $school !== null && $school->isWithinJurisdictionOf($root);
        }

        if ($this->isUnionDivisionCoordinator()) {
            $root = $this->appointment?->organization;
            $school = $report->organization;

            if ($root === null || $school === null || ! $school->isWithinJurisdictionOf($root)) {
                return false;
            }

            return in_array($report->category_code, $this->allowedDivisionReportCategories(), true);
        }

        return false;
    }

    public function canSubmitDepartmentDraft(DemoReport $report): bool
    {
        return $this->isDepartmentCoordinator()
            && (int) $this->appointment?->organization_id === (int) $report->organization_id
            && (int) $this->appointment?->organizational_unit_id === (int) $report->organizational_unit_id
            && $report->status === DemoReport::STATUS_DRAFT;
    }

    public function canApproveAndReleaseReport(DemoReport $report): bool
    {
        return $this->isSchoolLead()
            && (int) $this->appointment?->organization_id === (int) $report->organization_id
            && in_array($report->status, [DemoReport::STATUS_DRAFT, DemoReport::STATUS_SUBMITTED_LOCAL], true);
    }

    public function canRegisterSubordinateOrganization(Organization $parent): bool
    {
        $root = $this->appointment?->organization;
        if ($root === null || ! $parent->isWithinJurisdictionOf($root)) {
            return false;
        }

        if ($this->isUnionSuperAdmin()) {
            return in_array($parent->type, [
                Organization::TYPE_UNION,
                Organization::TYPE_DIOCESE,
                Organization::TYPE_DISTRICT,
            ], true);
        }

        if ($this->isDioceseOfficer()) {
            return in_array($parent->type, [
                Organization::TYPE_DIOCESE,
                Organization::TYPE_DISTRICT,
            ], true);
        }

        return false;
    }

    public function canCreateOrganizationUnder(Organization $parent, string $childType): bool
    {
        if (! $this->canRegisterSubordinateOrganization($parent)) {
            return false;
        }

        if ($this->isDioceseOfficer() && $childType === Organization::TYPE_DIOCESE) {
            return false;
        }

        return match ($childType) {
            Organization::TYPE_DIOCESE => $parent->type === Organization::TYPE_UNION,
            Organization::TYPE_DISTRICT => $parent->type === Organization::TYPE_DIOCESE,
            Organization::TYPE_SUNDAY_SCHOOL => $parent->type === Organization::TYPE_DISTRICT,
            default => false,
        };
    }

    public function canUpdateOrganization(Organization $organization): bool
    {
        if (! $this->isUnionSuperAdmin()) {
            return false;
        }

        $root = $this->appointment?->organization;

        return $root !== null && $organization->isWithinJurisdictionOf($root);
    }

    public function canManageLocalLeadAppointments(?Organization $school = null): bool
    {
        if (! $this->isUnionSuperAdmin()) {
            return false;
        }

        $root = $this->appointment?->organization;
        if ($root === null) {
            return false;
        }

        if ($school !== null) {
            return $school->type === Organization::TYPE_SUNDAY_SCHOOL
                && $school->isWithinJurisdictionOf($root);
        }

        return true;
    }

    public function canManageDepartmentCoordinatorAppointments(Organization $school): bool
    {
        return $this->canManageSchoolLocally($school);
    }

    public function canUpdateAppointment(ScopedAppointment $targetAppointment): bool
    {
        $targetAppointment->loadMissing('organization');
        $targetOrg = $targetAppointment->organization;

        if ($targetOrg === null) {
            return false;
        }

        if ($targetAppointment->role_key === ScopedAppointment::ROLE_SCHOOL_LEAD) {
            return $this->canManageLocalLeadAppointments($targetOrg);
        }

        if ($targetAppointment->role_key === ScopedAppointment::ROLE_DEPARTMENT_COORDINATOR) {
            return $this->canManageDepartmentCoordinatorAppointments($targetOrg);
        }

        return false;
    }

    /**
     * Format shared Inertia payload for the current user's scope and switchable contexts.
     *
     * @return array<string, mixed>
     */
    public function toInertiaPayload(): array
    {
        $locale = app()->getLocale();

        return [
            'active' => $this->activeContext === null ? null : [
                'key' => $this->activeContext['key'],
                'kind' => $this->activeContext['kind'],
                'roleKey' => $this->activeContext['roleKey'],
                'permissionCodes' => $this->permissionCodes(),
                'title' => $locale === 'en' ? $this->activeContext['titleEn'] : $this->activeContext['titleAm'],
                'titleAm' => $this->activeContext['titleAm'],
                'titleEn' => $this->activeContext['titleEn'],
                'organizationId' => $this->activeContext['organizationId'],
                'organizationName' => $locale === 'en' && $this->activeContext['organizationNameEn'] !== ''
                    ? $this->activeContext['organizationNameEn']
                    : $this->activeContext['organizationNameAm'],
                'organizationNameAm' => $this->activeContext['organizationNameAm'],
                'organizationNameEn' => $this->activeContext['organizationNameEn'],
                'organizationType' => $this->activeContext['organizationType'],
                'unitId' => $this->activeContext['unitId'],
                'unitName' => $locale === 'en' && ! empty($this->activeContext['unitNameEn'])
                    ? $this->activeContext['unitNameEn']
                    : $this->activeContext['unitNameAm'],
                'functionalCategory' => $this->activeContext['functionalCategory'],
                'startsOn' => $this->activeContext['startsOn'] ?? null,
                'endsOn' => $this->activeContext['endsOn'] ?? null,
            ],
            'available' => $this->availableContexts->map(fn (array $ctx): array => [
                'key' => $ctx['key'],
                'kind' => $ctx['kind'],
                'roleKey' => $ctx['roleKey'],
                'title' => $locale === 'en' ? $ctx['titleEn'] : $ctx['titleAm'],
                'organizationId' => $ctx['organizationId'],
                'organizationName' => $locale === 'en' && $ctx['organizationNameEn'] !== ''
                    ? $ctx['organizationNameEn']
                    : $ctx['organizationNameAm'],
                'unitName' => $locale === 'en' && ! empty($ctx['unitNameEn'])
                    ? $ctx['unitNameEn']
                    : $ctx['unitNameAm'],
            ])->values()->all(),
            'capabilities' => [
                'viewHierarchy' => $this->canViewHierarchyExplorer(),
                'viewReports' => $this->canViewReportsMenu(),
                'viewOwnSchool' => ($this->isSchoolLead() || $this->isDepartmentCoordinator()) ? $this->appointment?->organization_id : null,
                'viewOwnDepartment' => $this->isDepartmentCoordinator() ? $this->appointment?->organizational_unit_id : null,
                'viewMyProfile' => $this->membership !== null || $this->user !== null,
                'isUnionSuperAdmin' => $this->isUnionSuperAdmin(),
                'canManageLocalLeads' => $this->canManageLocalLeadAppointments(),
                'isPendingOrUnlinked' => $this->isPendingOrUnlinked(),
                'isApprovedMember' => $this->isApprovedMember(),
                'isTechnicalOperator' => $this->isTechnicalOperator(),
            ],
        ];
    }
}
