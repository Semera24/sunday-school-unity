<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import {
    AlertTriangle,
    Archive,
    Ban,
    CheckCircle2,
    Clock,
    FileSpreadsheet,
    FolderGit2,
    Mail,
    Search,
    ShieldCheck,
    UserPlus,
    Users,
    XCircle,
} from 'lucide-vue-next';
import { computed, reactive } from 'vue';
import InputError from '@/components/InputError.vue';
import PageContainer from '@/components/PageContainer.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useT } from '@/composables/useT';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

const t = useT();
const page = usePage();
const isEn = computed(() => page.props.locale === 'en');

const localizedText = (
    am: string | null | undefined,
    en?: string | null,
): string => {
    if (isEn.value && en) {
        return en;
    }

    return am ?? en ?? '—';
};

const categoryLabel = (code: string): string => {
    const translated = t(`demo.categories.${code}`);

    return translated.startsWith('demo.categories.') ? code : translated;
};

const statusLabel = (status: string): string => {
    const translated = t(`demo.statuses.${status}`);

    return translated.startsWith('demo.statuses.') ? status : translated;
};

type CoordinatorAppointmentRow = {
    id: number;
    userId: number;
    userName: string;
    userEmail: string;
    unitId: number | null;
    unitNameAm: string;
    unitNameEn?: string | null;
    titleAm: string;
    titleEn: string | null;
    startsOn: string | null;
    endsOn: string | null;
    isActive: boolean;
    canUpdate: boolean;
};

type Props = {
    school: {
        id: number;
        code: string;
        nameAm: string;
        nameEn: string | null;
        cityAm: string | null;
        cityEn: string | null;
        parishNameAm: string | null;
        parishNameEn: string | null;
        districtNameAm: string | null;
        districtNameEn?: string | null;
        dioceseNameAm: string | null;
        dioceseNameEn?: string | null;
        canManageLocally: boolean;
        canViewDirectory: boolean;
    };
    departments: {
        id: number;
        code: string;
        categoryCode: string;
        nameAm: string;
        nameEn: string | null;
        canOpen: boolean;
    }[];
    memberships: {
        id: number;
        membershipCode: string | null;
        status: string;
        accountLinkStatus?: string;
        statusReason?: string | null;
        fullName: string;
        nameEn: string | null;
        phone: string | null;
        accountEmail: string | null;
        pendingAccountEmail?: string | null;
        applicationNote: string | null;
        approvedBy: string | null;
        approvedAt: string | null;
        joinedOn?: string | null;
        departments: string[];
        canApprove: boolean;
    }[];
    membershipPagination?: {
        currentPage: number;
        lastPage: number;
        perPage: number;
        total: number;
        prevPageUrl: string | null;
        nextPageUrl: string | null;
    } | null;
    directoryFilters?: {
        search: string;
        status: string;
        department_id: number | string;
    };
    statusCounts?: {
        all: number;
        approved: number;
        pending: number;
        rejected: number;
        suspended: number;
        archived: number;
        pendingLinks: number;
    };
    leadReviewQueue?: {
        pendingMemberships: {
            id: number;
            membershipCode: string | null;
            fullName: string;
            nameEn: string | null;
            phone: string | null;
            accountEmail: string | null;
            applicationNote: string | null;
            createdAt: string | null;
        }[];
        pendingLinkInvitations: {
            id: number;
            membershipId: number;
            memberFullName: string;
            membershipCode: string | null;
            invitedEmail: string;
            acceptedByName: string | null;
            acceptedByEmail: string | null;
            acceptedAt: string | null;
        }[];
        activeInvitations: {
            id: number;
            membershipId: number;
            memberFullName: string;
            invitedEmail: string;
            expiresAt: string | null;
            isExpired: boolean;
            mailDeliveryStatus?: string;
            mailSentAt?: string | null;
            mailError?: string | null;
            createdBy: string | null;
        }[];
    };
    localDuplicateCandidates?: {
        membershipId: number;
        membershipCode: string | null;
        status: string;
        fullName: string;
        nameEn: string | null;
        phone: string | null;
    }[];
    coordinatorAppointments: CoordinatorAppointmentRow[];
    coordinatorCandidates: {
        id: number;
        name: string;
        email: string;
    }[];
    reports: {
        id: number;
        unitNameAm: string | null;
        unitNameEn?: string | null;
        categoryCode: string;
        periodLabel: string;
        titleAm: string;
        activityCount: number;
        status: string;
        submittedBy: string | null;
        approvedBy: string | null;
        releasedAt: string | null;
        canSubmitLocal: boolean;
        canReleaseUpward: boolean;
    }[];
};

const props = defineProps<Props>();

const schoolDisplayName = computed(() =>
    localizedText(props.school.nameAm, props.school.nameEn),
);

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: t('nav.dashboard'), href: '/dashboard' },
    { title: t('nav.hierarchy'), href: '/hierarchy' },
    { title: schoolDisplayName.value, href: `/schools/${props.school.id}` },
]);

const deptForm = useForm({
    code: '',
    category_code: 'education',
    name_am: '',
    name_en: '',
});

const createDepartment = (): void => {
    deptForm.post(`/schools/${props.school.id}/departments`, {
        preserveScroll: true,
        onSuccess: () => deptForm.reset('code', 'name_am', 'name_en'),
    });
};

const coordForm = useForm({
    organizational_unit_id: props.departments[0]?.id ?? '',
    user_id: props.coordinatorCandidates[0]?.id ?? '',
    title_am: 'የአገልግሎት ክፍል አስተባባሪ',
    title_en: 'Department Coordinator',
    starts_on: '2026-01-01',
    ends_on: '',
});

const createCoordinatorAppointment = (): void => {
    coordForm.post(`/schools/${props.school.id}/appointments`, {
        preserveScroll: true,
    });
};

const toggleCoordinatorAppointment = (
    appt: CoordinatorAppointmentRow,
): void => {
    router.patch(
        `/appointments/${appt.id}`,
        { is_active: !appt.isActive },
        { preserveScroll: true },
    );
};

const memberForm = useForm({
    full_name: '',
    name_en: '',
    phone: '',
    membership_code: '',
    joined_on: new Date().toISOString().slice(0, 10),
    initial_status: 'approved',
    department_ids: [] as number[],
    department_role_label_am: 'አባል',
    service_notes: '',
    application_note: '',
    invite_email: '',
    confirm_duplicate_override: false,
});

const possibleLocalDuplicates = computed(() => {
    const candidates = props.localDuplicateCandidates ?? [];
    const nameTerm = memberForm.full_name.trim().toLowerCase();
    const phoneTerm = memberForm.phone.trim();

    if (nameTerm.length < 2 && phoneTerm.length < 4) {
        return [];
    }

    return candidates.filter((c) => {
        const matchName =
            nameTerm.length >= 2 &&
            c.fullName.trim().toLowerCase() === nameTerm;
        const matchPhone =
            phoneTerm.length >= 4 &&
            c.phone !== null &&
            c.phone.trim() === phoneTerm;

        return matchName || matchPhone;
    });
});

const registerMember = (): void => {
    memberForm.post(`/schools/${props.school.id}/members`, {
        preserveScroll: true,
        onSuccess: () =>
            memberForm.reset(
                'full_name',
                'name_en',
                'phone',
                'membership_code',
                'department_ids',
                'service_notes',
                'application_note',
                'invite_email',
                'confirm_duplicate_override',
            ),
    });
};

const filterState = reactive({
    search: props.directoryFilters?.search ?? '',
    status: props.directoryFilters?.status ?? 'all',
    department_id: props.directoryFilters?.department_id ?? '',
});

const applyDirectoryFilters = (): void => {
    router.get(
        `/schools/${props.school.id}`,
        {
            search: filterState.search || undefined,
            status:
                filterState.status !== 'all' ? filterState.status : undefined,
            department_id: filterState.department_id || undefined,
        },
        {
            preserveScroll: true,
            preserveState: true,
        },
    );
};

const resetDirectoryFilters = (): void => {
    filterState.search = '';
    filterState.status = 'all';
    filterState.department_id = '';
    router.get(
        `/schools/${props.school.id}`,
        {},
        {
            preserveScroll: true,
        },
    );
};

const pageErrors = computed(
    () => (page.props.errors ?? {}) as Record<string, string>,
);

const mailStatusLabel = (status?: string | null): string => {
    if (status === 'failed') {
        return t('demo.invitations.mail_status_failed');
    }

    if (status === 'pending') {
        return t('demo.invitations.mail_status_pending');
    }

    return t('demo.invitations.mail_status_sent');
};

const reviewNotes = reactive<Record<string, string>>({});

const approveMember = (membershipId: number): void => {
    router.post(
        `/memberships/${membershipId}/status`,
        {
            action: 'approve',
            reason: reviewNotes[`m_${membershipId}`] ?? '',
        },
        { preserveScroll: true },
    );
};

const rejectMember = (membershipId: number): void => {
    router.post(
        `/memberships/${membershipId}/status`,
        {
            action: 'reject',
            reason: reviewNotes[`m_${membershipId}`] ?? '',
        },
        { preserveScroll: true },
    );
};

const approveInvitationLink = (invitationId: number): void => {
    router.post(
        `/invitations/${invitationId}/approve`,
        {
            review_note: reviewNotes[`inv_${invitationId}`] ?? '',
        },
        { preserveScroll: true },
    );
};

const rejectInvitationLink = (invitationId: number): void => {
    router.post(
        `/invitations/${invitationId}/reject`,
        {
            review_note: reviewNotes[`inv_${invitationId}`] ?? '',
        },
        { preserveScroll: true },
    );
};

const resendInvitation = (invitationId: number): void => {
    router.post(
        `/invitations/${invitationId}/resend`,
        {},
        { preserveScroll: true },
    );
};

const revokeInvitation = (invitationId: number): void => {
    router.post(
        `/invitations/${invitationId}/revoke`,
        {
            review_note: reviewNotes[`inv_${invitationId}`] ?? '',
        },
        { preserveScroll: true },
    );
};

const releaseReport = (reportId: number): void => {
    router.post(`/reports/${reportId}/release`, {}, { preserveScroll: true });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="schoolDisplayName" />
        <PageContainer>
            <PageHeader
                :title="schoolDisplayName"
                :description="
                    t('demo.school.header_meta', {
                        parish: localizedText(
                            school.parishNameAm,
                            school.parishNameEn,
                        ),
                        city: localizedText(school.cityAm, school.cityEn),
                        district: localizedText(
                            school.districtNameAm,
                            school.districtNameEn,
                        ),
                        diocese: localizedText(
                            school.dioceseNameAm,
                            school.dioceseNameEn,
                        ),
                    })
                "
            />

            <!-- Local Departments Section -->
            <section
                class="rounded-2xl border border-border/80 bg-card p-6 shadow-sm"
            >
                <div class="flex items-center gap-2 text-lg font-semibold">
                    <FolderGit2 class="size-5 text-primary" />
                    <h2>{{ t('demo.school.departments_title') }}</h2>
                </div>
                <div class="mt-4 grid gap-3 md:grid-cols-3">
                    <div
                        v-for="dept in departments"
                        :key="dept.id"
                        class="rounded-xl border border-border/70 bg-background p-4"
                    >
                        <div class="font-semibold text-foreground">
                            {{ localizedText(dept.nameAm, dept.nameEn) }}
                        </div>
                        <div class="mt-1 text-xs text-muted-foreground">
                            {{ t('demo.school.category_label') }}
                            {{ categoryLabel(dept.categoryCode) }}
                        </div>
                        <div class="mt-3">
                            <Button
                                v-if="dept.canOpen"
                                size="sm"
                                variant="outline"
                                as-child
                            >
                                <Link :href="`/departments/${dept.id}`">
                                    {{ t('demo.school.open_department') }}
                                </Link>
                            </Button>
                            <span v-else class="text-xs text-muted-foreground">
                                {{
                                    t('demo.school.department_restricted_note')
                                }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Create Department Form (Only for Local Lead of this School) -->
                <form
                    v-if="school.canManageLocally"
                    class="mt-6 grid gap-3 border-t border-border/60 pt-5 md:grid-cols-4"
                    @submit.prevent="createDepartment"
                >
                    <div class="text-sm font-semibold md:col-span-4">
                        {{ t('demo.school.add_department_title') }}
                    </div>
                    <div>
                        <Label for="dept_code">{{
                            t('demo.school.dept_code')
                        }}</Label>
                        <Input
                            id="dept_code"
                            v-model="deptForm.code"
                            placeholder="arts-dept"
                            required
                        />
                        <InputError :message="deptForm.errors.code" />
                    </div>
                    <div>
                        <Label for="dept_cat">{{
                            t('demo.school.dept_category')
                        }}</Label>
                        <select
                            id="dept_cat"
                            v-model="deptForm.category_code"
                            class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                        >
                            <option value="education">
                                {{ t('demo.categories.education') }}
                            </option>
                            <option value="arts">
                                {{ t('demo.categories.arts') }}
                            </option>
                            <option value="development">
                                {{ t('demo.categories.development') }}
                            </option>
                            <option value="income">
                                {{ t('demo.categories.income') }}
                            </option>
                            <option value="charity">
                                {{ t('demo.categories.charity') }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <Label for="dept_name_am">{{
                            t('demo.school.dept_name_am')
                        }}</Label>
                        <Input
                            id="dept_name_am"
                            v-model="deptForm.name_am"
                            placeholder="የኪነ ጥበብ ክፍል"
                            required
                        />
                        <InputError :message="deptForm.errors.name_am" />
                    </div>
                    <div class="flex items-end">
                        <Button type="submit" :disabled="deptForm.processing">
                            {{ t('demo.school.add_department_button') }}
                        </Button>
                    </div>
                </form>
            </section>

            <!-- Department Coordinator Appointments (Local Sunday School Lead Only) -->
            <section
                v-if="school.canManageLocally"
                class="rounded-2xl border border-border/80 bg-card p-6 shadow-sm"
            >
                <h2 class="text-lg font-semibold">
                    {{ t('demo.coordinator_appointments.title') }}
                </h2>
                <p class="mt-1 text-xs text-muted-foreground">
                    {{ t('demo.coordinator_appointments.description') }}
                </p>

                <form
                    class="mt-4 grid gap-4 md:grid-cols-3"
                    @submit.prevent="createCoordinatorAppointment"
                >
                    <div class="grid gap-1.5">
                        <Label for="coord_unit_id">{{
                            t('demo.coordinator_appointments.department')
                        }}</Label>
                        <select
                            id="coord_unit_id"
                            v-model="coordForm.organizational_unit_id"
                            class="h-10 rounded-md border border-input bg-background px-3 text-sm"
                        >
                            <option
                                v-for="dept in departments"
                                :key="dept.id"
                                :value="dept.id"
                            >
                                {{ localizedText(dept.nameAm, dept.nameEn) }}
                                ({{ dept.code }})
                            </option>
                        </select>
                        <InputError
                            :message="coordForm.errors.organizational_unit_id"
                        />
                    </div>

                    <div class="grid gap-1.5">
                        <Label for="coord_user_id">{{
                            t('demo.coordinator_appointments.user')
                        }}</Label>
                        <select
                            id="coord_user_id"
                            v-model="coordForm.user_id"
                            class="h-10 rounded-md border border-input bg-background px-3 text-sm"
                        >
                            <option
                                v-for="u in coordinatorCandidates"
                                :key="u.id"
                                :value="u.id"
                            >
                                {{ u.name }} ({{ u.email }})
                            </option>
                        </select>
                        <InputError :message="coordForm.errors.user_id" />
                    </div>

                    <div class="grid gap-1.5">
                        <Label for="coord_title_am">{{
                            t('demo.coordinator_appointments.title_am')
                        }}</Label>
                        <Input
                            id="coord_title_am"
                            v-model="coordForm.title_am"
                            required
                        />
                        <InputError :message="coordForm.errors.title_am" />
                    </div>

                    <div class="grid gap-1.5">
                        <Label for="coord_title_en">{{
                            t('demo.coordinator_appointments.title_en')
                        }}</Label>
                        <Input
                            id="coord_title_en"
                            v-model="coordForm.title_en"
                        />
                    </div>

                    <div class="grid gap-1.5">
                        <Label for="coord_starts_on">{{
                            t('demo.coordinator_appointments.starts_on')
                        }}</Label>
                        <Input
                            id="coord_starts_on"
                            v-model="coordForm.starts_on"
                            type="date"
                            required
                        />
                        <InputError :message="coordForm.errors.starts_on" />
                    </div>

                    <div class="grid gap-1.5">
                        <Label for="coord_ends_on">{{
                            t('demo.coordinator_appointments.ends_on')
                        }}</Label>
                        <Input
                            id="coord_ends_on"
                            v-model="coordForm.ends_on"
                            type="date"
                        />
                        <InputError :message="coordForm.errors.ends_on" />
                    </div>

                    <div class="md:col-span-3">
                        <Button type="submit" :disabled="coordForm.processing">
                            {{
                                t('demo.coordinator_appointments.assign_button')
                            }}
                        </Button>
                    </div>
                </form>

                <div
                    v-if="coordinatorAppointments.length > 0"
                    class="mt-5 border-t border-border/60 pt-4"
                >
                    <h3 class="text-sm font-semibold">
                        {{ t('demo.coordinator_appointments.current_list') }}
                    </h3>
                    <div class="mt-3 space-y-2">
                        <div
                            v-for="appt in coordinatorAppointments"
                            :key="appt.id"
                            class="flex flex-col justify-between gap-3 rounded-xl border border-border/70 bg-background p-3.5 text-sm md:flex-row md:items-center"
                        >
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="font-semibold">{{
                                        localizedText(
                                            appt.unitNameAm,
                                            appt.unitNameEn,
                                        )
                                    }}</span>
                                    <span
                                        class="rounded-full px-2 py-0.5 text-xs font-medium"
                                        :class="
                                            appt.isActive
                                                ? 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300'
                                                : 'bg-muted text-muted-foreground'
                                        "
                                    >
                                        {{
                                            appt.isActive
                                                ? t(
                                                      'demo.lead_appointments.active_badge',
                                                  )
                                                : t(
                                                      'demo.lead_appointments.inactive_badge',
                                                  )
                                        }}
                                    </span>
                                </div>
                                <div class="mt-1 text-xs text-muted-foreground">
                                    {{ appt.userName }} ({{ appt.userEmail }}) ·
                                    {{
                                        localizedText(
                                            appt.titleAm,
                                            appt.titleEn,
                                        )
                                    }}
                                    ·
                                    {{
                                        t('demo.lead_appointments.period', {
                                            start: appt.startsOn ?? '—',
                                            end:
                                                appt.endsOn ??
                                                t(
                                                    'demo.lead_appointments.open_ended',
                                                ),
                                        })
                                    }}
                                </div>
                            </div>

                            <div v-if="appt.canUpdate">
                                <Button
                                    size="sm"
                                    variant="outline"
                                    @click="toggleCoordinatorAppointment(appt)"
                                >
                                    {{
                                        appt.isActive
                                            ? t(
                                                  'demo.lead_appointments.deactivate',
                                              )
                                            : t(
                                                  'demo.lead_appointments.activate',
                                              )
                                    }}
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Local Lead Review Queue (Pending Memberships & Pending Account-Link Approvals) -->
            <section
                v-if="school.canManageLocally && leadReviewQueue"
                class="rounded-2xl border border-border/80 bg-card p-6 shadow-sm"
            >
                <div class="flex items-center gap-2 text-lg font-semibold">
                    <ShieldCheck class="size-5 text-primary" />
                    <h2>{{ t('demo.review_queue.title') }}</h2>
                </div>
                <p class="mt-1 text-xs text-muted-foreground">
                    {{ t('demo.review_queue.description') }}
                </p>

                <div
                    v-if="
                        pageErrors.reason ||
                        pageErrors.review_note ||
                        pageErrors.invitation ||
                        pageErrors.action
                    "
                    class="mt-3 space-y-1"
                >
                    <InputError :message="pageErrors.reason" />
                    <InputError :message="pageErrors.review_note" />
                    <InputError :message="pageErrors.invitation" />
                    <InputError :message="pageErrors.action" />
                </div>

                <!-- Pending Membership Applications -->
                <div
                    v-if="leadReviewQueue.pendingMemberships.length > 0"
                    class="mt-4 space-y-2"
                >
                    <h3 class="text-sm font-semibold">
                        {{
                            t('demo.review_queue.pending_memberships_title', {
                                count: leadReviewQueue.pendingMemberships
                                    .length,
                            })
                        }}
                    </h3>
                    <div
                        v-for="pm in leadReviewQueue.pendingMemberships"
                        :key="pm.id"
                        class="flex flex-col justify-between gap-3 rounded-xl border border-amber-500/30 bg-amber-500/5 p-4 text-sm md:flex-row md:items-center"
                    >
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <Link
                                    :href="`/members/${pm.id}`"
                                    class="font-semibold underline hover:text-primary"
                                >
                                    {{ pm.fullName }}
                                </Link>
                                <span
                                    v-if="pm.membershipCode"
                                    class="text-xs text-muted-foreground"
                                >
                                    ({{ pm.membershipCode }})
                                </span>
                            </div>
                            <div class="mt-1 text-xs text-muted-foreground">
                                {{
                                    pm.accountEmail ??
                                    t('demo.school.no_login_account')
                                }}
                                <span v-if="pm.phone"> · {{ pm.phone }}</span>
                            </div>
                            <div
                                v-if="pm.applicationNote"
                                class="mt-1 text-xs text-foreground/80"
                            >
                                {{ pm.applicationNote }}
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            <Input
                                v-model="reviewNotes[`m_${pm.id}`]"
                                :placeholder="
                                    t('demo.review_queue.reason_placeholder')
                                "
                                class="h-9 w-52 text-xs"
                            />
                            <Button size="sm" @click="approveMember(pm.id)">
                                {{ t('demo.review_queue.approve_membership') }}
                            </Button>
                            <Button
                                size="sm"
                                variant="outline"
                                @click="rejectMember(pm.id)"
                            >
                                {{ t('demo.review_queue.reject_membership') }}
                            </Button>
                        </div>
                    </div>
                </div>

                <!-- Pending Account-Link Invitations (Accepted by User, Awaiting Lead Approval) -->
                <div
                    v-if="leadReviewQueue.pendingLinkInvitations.length > 0"
                    class="mt-5 space-y-2 border-t border-border/60 pt-4"
                >
                    <h3 class="text-sm font-semibold">
                        {{
                            t('demo.review_queue.pending_links_title', {
                                count: leadReviewQueue.pendingLinkInvitations
                                    .length,
                            })
                        }}
                    </h3>
                    <div
                        v-for="inv in leadReviewQueue.pendingLinkInvitations"
                        :key="inv.id"
                        class="flex flex-col justify-between gap-3 rounded-xl border border-amber-500/30 bg-amber-500/5 p-4 text-sm md:flex-row md:items-center"
                    >
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <Link
                                    :href="`/members/${inv.membershipId}`"
                                    class="font-semibold underline hover:text-primary"
                                >
                                    {{ inv.memberFullName }}
                                </Link>
                                <span
                                    v-if="inv.membershipCode"
                                    class="text-xs text-muted-foreground"
                                >
                                    ({{ inv.membershipCode }})
                                </span>
                            </div>
                            <div class="mt-1 text-xs text-muted-foreground">
                                {{ t('demo.review_queue.invited_email_label') }}
                                {{ inv.invitedEmail }} ·
                                {{ t('demo.review_queue.accepted_by_label') }}
                                {{ inv.acceptedByName }} ({{
                                    inv.acceptedByEmail
                                }})
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            <Input
                                v-model="reviewNotes[`inv_${inv.id}`]"
                                :placeholder="
                                    t('demo.review_queue.reason_placeholder')
                                "
                                class="h-9 w-52 text-xs"
                            />
                            <Button
                                size="sm"
                                @click="approveInvitationLink(inv.id)"
                            >
                                {{ t('demo.review_queue.approve_link') }}
                            </Button>
                            <Button
                                size="sm"
                                variant="outline"
                                @click="rejectInvitationLink(inv.id)"
                            >
                                {{ t('demo.review_queue.reject_link') }}
                            </Button>
                        </div>
                    </div>
                </div>

                <!-- Active Unaccepted Invitations -->
                <div
                    v-if="leadReviewQueue.activeInvitations.length > 0"
                    class="mt-5 space-y-2 border-t border-border/60 pt-4"
                >
                    <h3 class="text-sm font-semibold">
                        {{
                            t('demo.review_queue.active_invitations_title', {
                                count: leadReviewQueue.activeInvitations.length,
                            })
                        }}
                    </h3>
                    <div
                        v-for="inv in leadReviewQueue.activeInvitations"
                        :key="inv.id"
                        class="flex flex-col justify-between gap-3 rounded-xl border border-border/70 bg-background p-3.5 text-sm md:flex-row md:items-center"
                    >
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <Link
                                    :href="`/members/${inv.membershipId}`"
                                    class="font-semibold underline hover:text-primary"
                                >
                                    {{ inv.memberFullName }}
                                </Link>
                                <span
                                    class="rounded-full px-2 py-0.5 text-[11px] font-medium"
                                    :class="
                                        inv.mailDeliveryStatus === 'failed'
                                            ? 'bg-destructive/15 text-destructive'
                                            : 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300'
                                    "
                                >
                                    {{
                                        mailStatusLabel(inv.mailDeliveryStatus)
                                    }}
                                </span>
                                <span
                                    v-if="inv.isExpired"
                                    class="rounded-full bg-amber-500/15 px-2 py-0.5 text-[11px] font-medium text-amber-700 dark:text-amber-300"
                                >
                                    {{ statusLabel('expired') }}
                                </span>
                            </div>
                            <div class="mt-1 text-xs text-muted-foreground">
                                {{ t('demo.review_queue.invited_email_label') }}
                                {{ inv.invitedEmail }} ·
                                {{ t('demo.review_queue.expires_at_label') }}
                                {{ inv.expiresAt ?? '—' }}
                            </div>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <Button
                                size="sm"
                                variant="secondary"
                                @click="resendInvitation(inv.id)"
                            >
                                {{ t('demo.invitations.resend_button') }}
                            </Button>
                            <Button
                                size="sm"
                                variant="outline"
                                @click="revokeInvitation(inv.id)"
                            >
                                {{ t('demo.review_queue.revoke_invitation') }}
                            </Button>
                        </div>
                    </div>
                </div>

                <p
                    v-if="
                        leadReviewQueue.pendingMemberships.length === 0 &&
                        leadReviewQueue.pendingLinkInvitations.length === 0 &&
                        leadReviewQueue.activeInvitations.length === 0
                    "
                    class="mt-4 text-xs text-muted-foreground"
                >
                    {{ t('demo.review_queue.empty_queue') }}
                </p>
            </section>

            <!-- Register Local Sunday School Member (Local Lead Only) -->
            <section
                v-if="school.canManageLocally"
                class="rounded-2xl border border-border/80 bg-card p-6 shadow-sm"
            >
                <div class="flex items-center gap-2 text-lg font-semibold">
                    <UserPlus class="size-5 text-primary" />
                    <h2>{{ t('demo.member_form.register_title') }}</h2>
                </div>
                <p class="mt-1 text-xs text-muted-foreground">
                    {{ t('demo.member_form.register_description') }}
                </p>

                <!-- Local Duplicate Suggestion Alert (Same School Only) -->
                <div
                    v-if="possibleLocalDuplicates.length > 0"
                    class="mt-4 rounded-xl border border-amber-500/40 bg-amber-500/10 p-4 text-sm"
                >
                    <div
                        class="flex items-center gap-2 font-semibold text-amber-800 dark:text-amber-300"
                    >
                        <AlertTriangle class="size-4" />
                        <span>{{
                            t('demo.member_form.duplicate_warning_title')
                        }}</span>
                    </div>
                    <p class="mt-1 text-xs text-muted-foreground">
                        {{ t('demo.member_form.duplicate_warning_body') }}
                    </p>
                    <ul class="mt-2 space-y-1 text-xs">
                        <li
                            v-for="dup in possibleLocalDuplicates"
                            :key="dup.membershipId"
                        >
                            <Link
                                :href="`/members/${dup.membershipId}`"
                                class="font-medium underline hover:text-primary"
                            >
                                {{ dup.fullName }}
                            </Link>
                            <span v-if="dup.phone"> · {{ dup.phone }}</span>
                            <span> · ({{ statusLabel(dup.status) }}) </span>
                        </li>
                    </ul>
                    <label
                        class="mt-3 flex items-center gap-2 text-xs font-medium"
                    >
                        <input
                            v-model="memberForm.confirm_duplicate_override"
                            type="checkbox"
                            class="size-4 rounded border-input"
                        />
                        <span>{{
                            t('demo.member_form.duplicate_confirm_label')
                        }}</span>
                    </label>
                </div>

                <form
                    class="mt-4 grid gap-4 md:grid-cols-3"
                    @submit.prevent="registerMember"
                >
                    <div class="grid gap-1.5">
                        <Label for="reg_full_name">{{
                            t('demo.member_form.full_name')
                        }}</Label>
                        <Input
                            id="reg_full_name"
                            v-model="memberForm.full_name"
                            required
                        />
                        <InputError :message="memberForm.errors.full_name" />
                    </div>

                    <div class="grid gap-1.5">
                        <Label for="reg_name_en">{{
                            t('demo.member_form.name_en')
                        }}</Label>
                        <Input id="reg_name_en" v-model="memberForm.name_en" />
                        <InputError :message="memberForm.errors.name_en" />
                    </div>

                    <div class="grid gap-1.5">
                        <Label for="reg_phone">{{
                            t('demo.member_form.phone')
                        }}</Label>
                        <Input id="reg_phone" v-model="memberForm.phone" />
                        <InputError :message="memberForm.errors.phone" />
                    </div>

                    <div class="grid gap-1.5">
                        <Label for="reg_code">{{
                            t('demo.member_form.membership_code')
                        }}</Label>
                        <Input
                            id="reg_code"
                            v-model="memberForm.membership_code"
                            placeholder="SSU-A-010"
                        />
                        <InputError
                            :message="memberForm.errors.membership_code"
                        />
                    </div>

                    <div class="grid gap-1.5">
                        <Label for="reg_joined_on">{{
                            t('demo.member_form.joined_on')
                        }}</Label>
                        <Input
                            id="reg_joined_on"
                            v-model="memberForm.joined_on"
                            type="date"
                        />
                        <InputError :message="memberForm.errors.joined_on" />
                    </div>

                    <div class="grid gap-1.5">
                        <Label for="reg_initial_status">{{
                            t('demo.member_form.initial_status')
                        }}</Label>
                        <select
                            id="reg_initial_status"
                            v-model="memberForm.initial_status"
                            class="h-10 rounded-md border border-input bg-background px-3 text-sm"
                        >
                            <option value="approved">
                                {{
                                    t(
                                        'demo.member_form.status_approved_immediate',
                                    )
                                }}
                            </option>
                            <option value="pending">
                                {{
                                    t('demo.member_form.status_pending_review')
                                }}
                            </option>
                        </select>
                        <InputError
                            :message="memberForm.errors.initial_status"
                        />
                    </div>

                    <div class="grid gap-1.5 md:col-span-2">
                        <Label>{{
                            t('demo.member_form.initial_departments')
                        }}</Label>
                        <div class="flex flex-wrap gap-3 pt-1">
                            <label
                                v-for="dept in departments"
                                :key="dept.id"
                                class="flex items-center gap-2 rounded-lg border border-border/70 bg-background px-3 py-1.5 text-xs"
                            >
                                <input
                                    v-model="memberForm.department_ids"
                                    type="checkbox"
                                    :value="dept.id"
                                    class="size-4 rounded border-input"
                                />
                                <span>{{
                                    localizedText(dept.nameAm, dept.nameEn)
                                }}</span>
                            </label>
                        </div>
                        <InputError
                            :message="memberForm.errors.department_ids"
                        />
                    </div>

                    <div class="grid gap-1.5">
                        <Label for="reg_dept_role">{{
                            t('demo.member_form.department_role_label')
                        }}</Label>
                        <Input
                            id="reg_dept_role"
                            v-model="memberForm.department_role_label_am"
                        />
                    </div>

                    <div class="grid gap-1.5">
                        <Label for="reg_service_notes">{{
                            t('demo.member_form.service_notes')
                        }}</Label>
                        <Input
                            id="reg_service_notes"
                            v-model="memberForm.service_notes"
                        />
                        <InputError
                            :message="memberForm.errors.service_notes"
                        />
                    </div>

                    <div class="grid gap-1.5">
                        <Label for="reg_application_note">{{
                            t('demo.member_form.application_note')
                        }}</Label>
                        <Input
                            id="reg_application_note"
                            v-model="memberForm.application_note"
                        />
                        <InputError
                            :message="memberForm.errors.application_note"
                        />
                    </div>

                    <div class="grid gap-1.5">
                        <Label for="reg_invite_email">{{
                            t('demo.member_form.invite_email')
                        }}</Label>
                        <Input
                            id="reg_invite_email"
                            v-model="memberForm.invite_email"
                            type="email"
                            placeholder="new-member@example.test"
                        />
                        <p class="text-[11px] text-muted-foreground">
                            {{ t('demo.member_form.invite_email_help') }}
                        </p>
                        <InputError :message="memberForm.errors.invite_email" />
                    </div>

                    <div class="md:col-span-3">
                        <Button type="submit" :disabled="memberForm.processing">
                            {{ t('demo.member_form.submit_register') }}
                        </Button>
                    </div>
                </form>
            </section>

            <!-- Local Members Directory (Visible ONLY to Local Lead of this School) -->
            <section
                v-if="school.canViewDirectory"
                class="rounded-2xl border border-border/80 bg-card p-6 shadow-sm"
            >
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <div
                            class="flex items-center gap-2 text-lg font-semibold"
                        >
                            <Users class="size-5 text-primary" />
                            <h2>{{ t('demo.school.members_title') }}</h2>
                        </div>
                        <p class="mt-1 text-xs text-muted-foreground">
                            {{ t('demo.school.members_description') }}
                        </p>
                    </div>

                    <div
                        v-if="statusCounts"
                        class="rounded-xl border border-border/70 bg-background px-3 py-1.5 text-xs text-muted-foreground"
                    >
                        {{
                            t('demo.directory.summary_counts', {
                                all: statusCounts.all,
                                approved: statusCounts.approved,
                                pending: statusCounts.pending,
                                suspended: statusCounts.suspended,
                                archived: statusCounts.archived,
                                rejected: statusCounts.rejected,
                            })
                        }}
                    </div>
                </div>

                <!-- Search & Filters -->
                <form
                    class="mt-4 grid gap-3 rounded-xl border border-border/60 bg-background p-3.5 md:grid-cols-4"
                    @submit.prevent="applyDirectoryFilters"
                >
                    <div class="md:col-span-2">
                        <Label for="dir_search" class="sr-only">{{
                            t('common.search')
                        }}</Label>
                        <div class="relative">
                            <Search
                                class="pointer-events-none absolute top-2.5 left-3 size-4 text-muted-foreground"
                            />
                            <Input
                                id="dir_search"
                                v-model="filterState.search"
                                class="pl-9"
                                :placeholder="
                                    t('demo.directory.search_placeholder')
                                "
                            />
                        </div>
                    </div>

                    <div>
                        <select
                            v-model="filterState.status"
                            class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                        >
                            <option value="all">
                                {{ t('demo.directory.filter_all_statuses') }}
                            </option>
                            <option value="approved">
                                {{ statusLabel('approved') }}
                            </option>
                            <option value="pending">
                                {{ statusLabel('pending') }}
                            </option>
                            <option value="suspended">
                                {{ statusLabel('suspended') }}
                            </option>
                            <option value="archived">
                                {{ statusLabel('archived') }}
                            </option>
                            <option value="rejected">
                                {{ statusLabel('rejected') }}
                            </option>
                        </select>
                    </div>

                    <div class="flex items-center gap-2">
                        <select
                            v-model="filterState.department_id"
                            class="h-10 flex-1 rounded-md border border-input bg-background px-3 text-sm"
                        >
                            <option value="">
                                {{ t('demo.directory.filter_all_departments') }}
                            </option>
                            <option
                                v-for="dept in departments"
                                :key="dept.id"
                                :value="dept.id"
                            >
                                {{ localizedText(dept.nameAm, dept.nameEn) }}
                            </option>
                        </select>
                        <Button type="submit" size="sm">
                            {{ t('demo.directory.apply_filters') }}
                        </Button>
                        <Button
                            type="button"
                            size="sm"
                            variant="outline"
                            @click="resetDirectoryFilters"
                        >
                            {{ t('demo.directory.reset_filters') }}
                        </Button>
                    </div>
                </form>

                <div class="mt-4 overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead
                            class="border-b text-xs text-muted-foreground uppercase"
                        >
                            <tr>
                                <th class="py-2 pr-4">
                                    {{ t('demo.school.col_full_name') }}
                                </th>
                                <th class="py-2 pr-4">
                                    {{ t('demo.school.col_account_link') }}
                                </th>
                                <th class="py-2 pr-4">
                                    {{ t('demo.school.col_departments') }}
                                </th>
                                <th class="py-2 pr-4">
                                    {{ t('demo.school.col_status') }}
                                </th>
                                <th class="py-2">
                                    {{ t('demo.school.col_action') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <tr v-for="m in memberships" :key="m.id">
                                <td class="py-3 pr-4 font-medium">
                                    <Link
                                        :href="`/members/${m.id}`"
                                        class="underline hover:text-primary"
                                    >
                                        {{ m.fullName }}
                                    </Link>
                                    <div class="text-xs text-muted-foreground">
                                        <span v-if="m.membershipCode">{{
                                            m.membershipCode
                                        }}</span>
                                        <span v-if="m.nameEn">
                                            · {{ m.nameEn }}
                                        </span>
                                        <span v-if="m.phone">
                                            · {{ m.phone }}
                                        </span>
                                    </div>
                                </td>
                                <td class="py-3 pr-4 text-xs">
                                    <div v-if="m.accountEmail">
                                        {{ m.accountEmail }}
                                    </div>
                                    <div
                                        v-else-if="m.pendingAccountEmail"
                                        class="inline-flex items-center gap-1 rounded-full bg-amber-500/15 px-2 py-0.5 text-amber-700 dark:text-amber-300"
                                    >
                                        <Mail class="size-3" />
                                        {{
                                            t(
                                                'demo.directory.pending_link_badge',
                                                {
                                                    email: m.pendingAccountEmail,
                                                },
                                            )
                                        }}
                                    </div>
                                    <span v-else class="text-muted-foreground">
                                        {{ t('demo.school.no_login_account') }}
                                    </span>
                                </td>
                                <td class="py-3 pr-4 text-xs">
                                    <span v-if="m.departments.length">{{
                                        m.departments.join(' · ')
                                    }}</span>
                                    <span v-else class="text-muted-foreground"
                                        >—</span
                                    >
                                </td>
                                <td class="py-3 pr-4">
                                    <span
                                        v-if="m.status === 'approved'"
                                        class="inline-flex items-center gap-1 rounded-full bg-emerald-500/15 px-2.5 py-0.5 text-xs font-medium text-emerald-700 dark:text-emerald-300"
                                    >
                                        <CheckCircle2 class="size-3" />
                                        {{ statusLabel(m.status) }}
                                    </span>
                                    <span
                                        v-else-if="m.status === 'pending'"
                                        class="inline-flex items-center gap-1 rounded-full bg-amber-500/15 px-2.5 py-0.5 text-xs font-medium text-amber-700 dark:text-amber-300"
                                    >
                                        <Clock class="size-3" />
                                        {{ statusLabel(m.status) }}
                                    </span>
                                    <span
                                        v-else-if="m.status === 'suspended'"
                                        class="inline-flex items-center gap-1 rounded-full bg-rose-500/15 px-2.5 py-0.5 text-xs font-medium text-rose-700 dark:text-rose-300"
                                    >
                                        <Ban class="size-3" />
                                        {{ statusLabel(m.status) }}
                                    </span>
                                    <span
                                        v-else-if="m.status === 'rejected'"
                                        class="inline-flex items-center gap-1 rounded-full bg-destructive/15 px-2.5 py-0.5 text-xs font-medium text-destructive"
                                    >
                                        <XCircle class="size-3" />
                                        {{ statusLabel(m.status) }}
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center gap-1 rounded-full bg-muted px-2.5 py-0.5 text-xs font-medium text-muted-foreground"
                                    >
                                        <Archive class="size-3" />
                                        {{ statusLabel(m.status) }}
                                    </span>
                                </td>
                                <td class="py-3">
                                    <div class="flex items-center gap-2">
                                        <Button
                                            v-if="m.canApprove"
                                            size="sm"
                                            variant="default"
                                            @click="approveMember(m.id)"
                                        >
                                            {{
                                                t('demo.school.approve_button')
                                            }}
                                        </Button>
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            as-child
                                        >
                                            <Link :href="`/members/${m.id}`">
                                                {{ t('common.view_details') }}
                                            </Link>
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="memberships.length === 0">
                                <td
                                    colspan="5"
                                    class="py-6 text-center text-sm text-muted-foreground"
                                >
                                    {{ t('demo.directory.empty_filtered') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Controls -->
                <div
                    v-if="
                        membershipPagination &&
                        membershipPagination.lastPage > 1
                    "
                    class="mt-4 flex items-center justify-between border-t border-border/60 pt-4 text-xs text-muted-foreground"
                >
                    <span>
                        {{
                            t('demo.directory.pagination_summary', {
                                current: membershipPagination.currentPage,
                                last: membershipPagination.lastPage,
                                total: membershipPagination.total,
                            })
                        }}
                    </span>
                    <div class="flex items-center gap-2">
                        <Button
                            v-if="membershipPagination.prevPageUrl"
                            size="sm"
                            variant="outline"
                            as-child
                        >
                            <Link :href="membershipPagination.prevPageUrl">
                                {{ t('demo.directory.prev_page') }}
                            </Link>
                        </Button>
                        <Button
                            v-if="membershipPagination.nextPageUrl"
                            size="sm"
                            variant="outline"
                            as-child
                        >
                            <Link :href="membershipPagination.nextPageUrl">
                                {{ t('demo.directory.next_page') }}
                            </Link>
                        </Button>
                    </div>
                </div>
            </section>

            <section
                v-else
                class="rounded-2xl border border-dashed border-border/70 bg-muted/20 p-5 text-sm text-muted-foreground"
            >
                {{ t('demo.school.directory_hidden_notice') }}
            </section>

            <!-- Scoped Reports for this School -->
            <section
                class="rounded-2xl border border-border/80 bg-card p-6 shadow-sm"
            >
                <div class="flex items-center gap-2 text-lg font-semibold">
                    <FileSpreadsheet class="size-5 text-primary" />
                    <h2>{{ t('demo.school.reports_title') }}</h2>
                </div>
                <div class="mt-4 space-y-3">
                    <div
                        v-for="rep in reports"
                        :key="rep.id"
                        class="flex flex-col justify-between gap-3 rounded-xl border border-border/70 bg-background p-4 md:flex-row md:items-center"
                    >
                        <div>
                            <div class="flex items-center gap-2">
                                <span
                                    class="rounded-full px-2.5 py-0.5 text-xs font-semibold"
                                    :class="
                                        rep.status === 'released'
                                            ? 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300'
                                            : 'bg-amber-500/15 text-amber-700 dark:text-amber-300'
                                    "
                                >
                                    {{ statusLabel(rep.status) }}
                                </span>
                                <span class="text-xs text-muted-foreground">
                                    {{ rep.periodLabel }} ·
                                    {{
                                        rep.unitNameAm
                                            ? localizedText(
                                                  rep.unitNameAm,
                                                  rep.unitNameEn,
                                              )
                                            : categoryLabel(rep.categoryCode)
                                    }}
                                </span>
                            </div>
                            <Link
                                :href="`/reports/${rep.id}`"
                                class="mt-1 block font-semibold underline hover:text-primary"
                            >
                                {{ rep.titleAm }}
                            </Link>
                        </div>

                        <div class="flex items-center gap-2">
                            <Button
                                v-if="rep.canReleaseUpward"
                                size="sm"
                                @click="releaseReport(rep.id)"
                            >
                                {{ t('demo.school.approve_release_button') }}
                            </Button>
                            <Button size="sm" variant="outline" as-child>
                                <Link :href="`/reports/${rep.id}`">{{
                                    t('common.view_details')
                                }}</Link>
                            </Button>
                        </div>
                    </div>

                    <p
                        v-if="reports.length === 0"
                        class="text-sm text-muted-foreground"
                    >
                        {{ t('demo.school.no_reports') }}
                    </p>
                </div>
            </section>
        </PageContainer>
    </AppLayout>
</template>
