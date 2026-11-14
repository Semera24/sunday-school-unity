<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import {
    FolderGit2,
    History,
    Mail,
    ShieldCheck,
    UserCheck,
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

const statusLabel = (status: string): string => {
    const translated = t(`demo.statuses.${status}`);

    return translated.startsWith('demo.statuses.') ? status : translated;
};

const decisionActionLabel = (action: string): string => {
    const translated = t(`demo.decision_actions.${action}`);

    return translated.startsWith('demo.decision_actions.')
        ? action
        : translated;
};

type DepartmentParticipationRow = {
    id: number;
    unitId: number;
    nameAm: string;
    nameEn?: string | null;
    roleLabelAm: string | null;
    startsOn: string | null;
    endsOn: string | null;
    isActive: boolean;
    isCurrentlyEffective?: boolean;
    canStartNewPeriod?: boolean;
    assignedBy?: string | null;
    endedBy?: string | null;
};

type DecisionRow = {
    id: number;
    action: string;
    fromStatus: string | null;
    toStatus: string;
    reason: string | null;
    actorName: string | null;
    targetUserEmail?: string | null;
    createdAt: string | null;
};

type InvitationRow = {
    id: number;
    invitedEmail: string;
    status: string;
    mailDeliveryStatus?: string;
    mailSentAt?: string | null;
    mailError?: string | null;
    expiresAt: string | null;
    acceptedAt: string | null;
    acceptedByName: string | null;
    acceptedByEmail: string | null;
    reviewedByName: string | null;
    reviewedAt: string | null;
    reviewNote: string | null;
    canApproveOrReject?: boolean;
    canResend?: boolean;
    canRevoke?: boolean;
};

type Props = {
    member: {
        id: number;
        membershipCode: string | null;
        status: string;
        accountLinkStatus?: string;
        statusReason?: string | null;
        fullName: string;
        nameEn: string | null;
        phone: string | null;
        serviceNotes: string | null;
        joinedOn?: string | null;
        endedOn?: string | null;
        schoolId: number;
        schoolNameAm: string | null;
        schoolNameEn?: string | null;
        accountEmail: string | null;
        pendingAccountEmail?: string | null;
        applicationNote: string | null;
        approvedBy: string | null;
        approvedAt: string | null;
        rejectedBy?: string | null;
        rejectedAt?: string | null;
        suspendedBy?: string | null;
        suspendedAt?: string | null;
        archivedBy?: string | null;
        archivedAt?: string | null;
        departments: {
            id: number;
            nameAm: string;
            nameEn?: string | null;
            roleLabelAm: string | null;
        }[];
        departmentParticipations?: DepartmentParticipationRow[];
        decisions?: DecisionRow[];
        invitations?: InvitationRow[];
        canApprove: boolean;
        canManageMember?: boolean;
        canIssueInvitation?: boolean;
        hasPendingLeadReview?: boolean;
    };
    availableDepartments?: {
        id: number;
        code: string;
        nameAm: string;
        nameEn?: string | null;
    }[];
};

const props = defineProps<Props>();

const schoolDisplayName = computed(() =>
    localizedText(props.member.schoolNameAm, props.member.schoolNameEn),
);

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: t('nav.dashboard'), href: '/dashboard' },
    {
        title: schoolDisplayName.value,
        href: `/schools/${props.member.schoolId}`,
    },
    { title: props.member.fullName, href: `/members/${props.member.id}` },
]);

const editForm = useForm({
    full_name: props.member.fullName,
    name_en: props.member.nameEn ?? '',
    phone: props.member.phone ?? '',
    membership_code: props.member.membershipCode ?? '',
    joined_on: props.member.joinedOn ?? '',
    service_notes: props.member.serviceNotes ?? '',
});

const submitMemberUpdate = (): void => {
    editForm.put(`/members/${props.member.id}`, {
        preserveScroll: true,
    });
};

const defaultLifecycleAction = computed(() => {
    if (props.member.status === 'pending') {
        return 'approve';
    }

    if (props.member.status === 'approved') {
        return 'suspend';
    }

    return 'reactivate';
});

const lifecycleForm = useForm({
    action: defaultLifecycleAction.value,
    reason: '',
});

const submitLifecycleTransition = (): void => {
    lifecycleForm.post(`/memberships/${props.member.id}/status`, {
        preserveScroll: true,
        onSuccess: () => lifecycleForm.reset('reason'),
    });
};

const approveMembership = (): void => {
    router.post(
        `/memberships/${props.member.id}/approve`,
        {},
        { preserveScroll: true },
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

const inviteForm = useForm({
    school_membership_id: props.member.id,
    invited_email: '',
});

const sendInvitation = (): void => {
    inviteForm.post(`/schools/${props.member.schoolId}/invitations`, {
        preserveScroll: true,
        onSuccess: () => inviteForm.reset('invited_email'),
    });
};

const invitationNotes = reactive<Record<number, string>>({});

const approveInvitationLink = (invitationId: number): void => {
    router.post(
        `/invitations/${invitationId}/approve`,
        {
            review_note: invitationNotes[invitationId] ?? '',
        },
        { preserveScroll: true },
    );
};

const rejectInvitationLink = (invitationId: number): void => {
    router.post(
        `/invitations/${invitationId}/reject`,
        {
            review_note: invitationNotes[invitationId] ?? '',
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
            review_note: invitationNotes[invitationId] ?? '',
        },
        { preserveScroll: true },
    );
};

const deptAssignForm = useForm({
    organizational_unit_id: props.availableDepartments?.[0]?.id ?? '',
    role_label_am: 'አባል',
    starts_on: new Date().toISOString().slice(0, 10),
    ends_on: '',
});

const assignDepartment = (): void => {
    deptAssignForm.post(`/members/${props.member.id}/departments`, {
        preserveScroll: true,
        onSuccess: () => deptAssignForm.reset('ends_on'),
    });
};

const dmActionDates = reactive<Record<number, string>>({});

const endDepartmentParticipation = (dmId: number): void => {
    router.patch(
        `/department-memberships/${dmId}`,
        {
            action: 'end',
            ends_on:
                dmActionDates[dmId] || new Date().toISOString().slice(0, 10),
        },
        { preserveScroll: true },
    );
};

const reactivateDepartmentParticipation = (dmId: number): void => {
    router.patch(
        `/department-memberships/${dmId}`,
        {
            action: 'reactivate',
            starts_on:
                dmActionDates[dmId] || new Date().toISOString().slice(0, 10),
        },
        { preserveScroll: true },
    );
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="member.fullName" />
        <PageContainer>
            <PageHeader
                :title="member.fullName"
                :description="
                    t('demo.member.header_meta', {
                        school: schoolDisplayName,
                        code: member.membershipCode ?? '—',
                    })
                "
            >
                <template #actions>
                    <Button v-if="member.canApprove" @click="approveMembership">
                        {{ t('demo.member.approve_button') }}
                    </Button>
                </template>
            </PageHeader>

            <!-- Summary Profile Card -->
            <section
                class="grid gap-4 rounded-2xl border border-border/80 bg-card p-6 shadow-sm md:grid-cols-2"
            >
                <div>
                    <div class="text-xs text-muted-foreground">
                        {{ t('demo.member.full_name_label') }}
                    </div>
                    <div class="mt-1 font-semibold">{{ member.fullName }}</div>
                </div>
                <div>
                    <div class="text-xs text-muted-foreground">
                        {{ t('demo.member.name_en_label') }}
                    </div>
                    <div class="mt-1">{{ member.nameEn ?? '—' }}</div>
                </div>
                <div>
                    <div class="text-xs text-muted-foreground">
                        {{ t('demo.member.status_label') }}
                    </div>
                    <div class="mt-1 font-medium">
                        {{ statusLabel(member.status) }}
                    </div>
                    <div
                        v-if="member.statusReason"
                        class="mt-1 text-xs text-muted-foreground"
                    >
                        {{ member.statusReason }}
                    </div>
                </div>
                <div>
                    <div class="text-xs text-muted-foreground">
                        {{ t('demo.member.linked_account_label') }}
                    </div>
                    <div class="mt-1">
                        <span v-if="member.accountEmail">{{
                            member.accountEmail
                        }}</span>
                        <span
                            v-else-if="member.pendingAccountEmail"
                            class="text-amber-700 dark:text-amber-300"
                        >
                            {{
                                t('demo.directory.pending_link_badge', {
                                    email: member.pendingAccountEmail,
                                })
                            }}
                        </span>
                        <span v-else>{{
                            t('demo.member.no_login_account')
                        }}</span>
                    </div>
                </div>
                <div>
                    <div class="text-xs text-muted-foreground">
                        {{ t('demo.member.phone_label') }}
                    </div>
                    <div class="mt-1">{{ member.phone ?? '—' }}</div>
                </div>
                <div>
                    <div class="text-xs text-muted-foreground">
                        {{ t('demo.member.approved_by_label') }}
                    </div>
                    <div class="mt-1">
                        {{ member.approvedBy ?? '—' }} ({{
                            member.approvedAt ?? '—'
                        }})
                    </div>
                </div>
                <div class="md:col-span-2">
                    <div class="text-xs text-muted-foreground">
                        {{ t('demo.member.service_notes_label') }}
                    </div>
                    <div class="mt-1 whitespace-pre-wrap">
                        {{
                            member.serviceNotes ?? member.applicationNote ?? '—'
                        }}
                    </div>
                </div>
                <div class="md:col-span-2">
                    <div class="text-xs text-muted-foreground">
                        {{ t('demo.member.departments_label') }}
                    </div>
                    <div class="mt-2 flex flex-wrap gap-2">
                        <span
                            v-for="d in member.departments"
                            :key="d.id"
                            class="rounded-lg border bg-background px-3 py-1 text-xs"
                        >
                            {{ localizedText(d.nameAm, d.nameEn) }} ({{
                                d.roleLabelAm ??
                                t('common.member_default_role')
                            }})
                        </span>
                        <span
                            v-if="member.departments.length === 0"
                            class="text-xs text-muted-foreground"
                            >—</span
                        >
                    </div>
                </div>
            </section>

            <!-- Edit Member Record Form (School Lead Only) -->
            <section
                v-if="member.canManageMember"
                class="rounded-2xl border border-border/80 bg-card p-6 shadow-sm"
            >
                <div class="flex items-center gap-2 text-lg font-semibold">
                    <UserCheck class="size-5 text-primary" />
                    <h2>{{ t('demo.member_form.edit_title') }}</h2>
                </div>
                <form
                    class="mt-4 grid gap-4 md:grid-cols-3"
                    @submit.prevent="submitMemberUpdate"
                >
                    <div class="grid gap-1.5">
                        <Label for="edit_full_name">{{
                            t('demo.member_form.full_name')
                        }}</Label>
                        <Input
                            id="edit_full_name"
                            v-model="editForm.full_name"
                            required
                        />
                        <InputError :message="editForm.errors.full_name" />
                    </div>

                    <div class="grid gap-1.5">
                        <Label for="edit_name_en">{{
                            t('demo.member_form.name_en')
                        }}</Label>
                        <Input id="edit_name_en" v-model="editForm.name_en" />
                        <InputError :message="editForm.errors.name_en" />
                    </div>

                    <div class="grid gap-1.5">
                        <Label for="edit_phone">{{
                            t('demo.member_form.phone')
                        }}</Label>
                        <Input id="edit_phone" v-model="editForm.phone" />
                        <InputError :message="editForm.errors.phone" />
                    </div>

                    <div class="grid gap-1.5">
                        <Label for="edit_code">{{
                            t('demo.member_form.membership_code')
                        }}</Label>
                        <Input
                            id="edit_code"
                            v-model="editForm.membership_code"
                        />
                        <InputError
                            :message="editForm.errors.membership_code"
                        />
                    </div>

                    <div class="grid gap-1.5">
                        <Label for="edit_joined_on">{{
                            t('demo.member_form.joined_on')
                        }}</Label>
                        <Input
                            id="edit_joined_on"
                            v-model="editForm.joined_on"
                            type="date"
                        />
                        <InputError :message="editForm.errors.joined_on" />
                    </div>

                    <div class="grid gap-1.5 md:col-span-3">
                        <Label for="edit_service_notes">{{
                            t('demo.member_form.service_notes')
                        }}</Label>
                        <Input
                            id="edit_service_notes"
                            v-model="editForm.service_notes"
                        />
                        <InputError :message="editForm.errors.service_notes" />
                    </div>

                    <div class="md:col-span-3">
                        <Button type="submit" :disabled="editForm.processing">
                            {{ t('demo.member_form.submit_update') }}
                        </Button>
                    </div>
                </form>
            </section>

            <!-- Membership Lifecycle Transitions (School Lead Only) -->
            <section
                v-if="member.canManageMember"
                class="rounded-2xl border border-border/80 bg-card p-6 shadow-sm"
            >
                <div class="flex items-center gap-2 text-lg font-semibold">
                    <ShieldCheck class="size-5 text-primary" />
                    <h2>{{ t('demo.lifecycle.title') }}</h2>
                </div>
                <p class="mt-1 text-xs text-muted-foreground">
                    {{ t('demo.lifecycle.description') }}
                </p>

                <form
                    class="mt-4 grid gap-4 md:grid-cols-3"
                    @submit.prevent="submitLifecycleTransition"
                >
                    <div class="grid gap-1.5">
                        <Label for="lifecycle_action">{{
                            t('demo.lifecycle.action_label')
                        }}</Label>
                        <select
                            id="lifecycle_action"
                            v-model="lifecycleForm.action"
                            class="h-10 rounded-md border border-input bg-background px-3 text-sm"
                        >
                            <option
                                v-if="member.status === 'pending'"
                                value="approve"
                            >
                                {{ t('demo.lifecycle.action_approve') }}
                            </option>
                            <option
                                v-if="member.status === 'pending'"
                                value="reject"
                            >
                                {{ t('demo.lifecycle.action_reject') }}
                            </option>
                            <option
                                v-if="member.status === 'approved'"
                                value="suspend"
                            >
                                {{ t('demo.lifecycle.action_suspend') }}
                            </option>
                            <option
                                v-if="
                                    [
                                        'approved',
                                        'suspended',
                                        'rejected',
                                    ].includes(member.status)
                                "
                                value="archive"
                            >
                                {{ t('demo.lifecycle.action_archive') }}
                            </option>
                            <option
                                v-if="
                                    [
                                        'suspended',
                                        'archived',
                                        'rejected',
                                    ].includes(member.status)
                                "
                                value="reactivate"
                            >
                                {{ t('demo.lifecycle.action_reactivate') }}
                            </option>
                        </select>
                        <InputError :message="lifecycleForm.errors.action" />
                    </div>

                    <div class="grid gap-1.5 md:col-span-2">
                        <Label for="lifecycle_reason">{{
                            t('demo.lifecycle.reason_label')
                        }}</Label>
                        <Input
                            id="lifecycle_reason"
                            v-model="lifecycleForm.reason"
                            :placeholder="
                                t('demo.lifecycle.reason_placeholder')
                            "
                        />
                        <InputError :message="lifecycleForm.errors.reason" />
                    </div>

                    <div class="md:col-span-3">
                        <Button
                            type="submit"
                            :disabled="lifecycleForm.processing"
                        >
                            {{ t('demo.lifecycle.submit_button') }}
                        </Button>
                    </div>
                </form>
            </section>

            <!-- Account Onboarding & Link Invitations (School Lead Only) -->
            <section
                v-if="member.canManageMember"
                class="rounded-2xl border border-border/80 bg-card p-6 shadow-sm"
            >
                <div class="flex items-center gap-2 text-lg font-semibold">
                    <Mail class="size-5 text-primary" />
                    <h2>{{ t('demo.invitations.manage_title') }}</h2>
                </div>
                <p class="mt-1 text-xs text-muted-foreground">
                    {{ t('demo.invitations.manage_description') }}
                </p>

                <div
                    v-if="pageErrors.invitation || pageErrors.review_note"
                    class="mt-3 space-y-1"
                >
                    <InputError :message="pageErrors.invitation" />
                    <InputError :message="pageErrors.review_note" />
                </div>

                <div
                    v-if="member.hasPendingLeadReview"
                    class="mt-4 rounded-xl border border-amber-500/30 bg-amber-500/5 p-3.5 text-xs text-amber-800 dark:text-amber-300"
                >
                    {{ t('demo.invitations.pending_review_notice') }}
                </div>

                <form
                    v-else-if="
                        member.canIssueInvitation !== false &&
                        !member.accountEmail
                    "
                    class="mt-4 flex flex-wrap items-end gap-3"
                    @submit.prevent="sendInvitation"
                >
                    <div class="grid min-w-64 flex-1 gap-1.5">
                        <Label for="invite_email">{{
                            t('demo.invitations.email_label')
                        }}</Label>
                        <Input
                            id="invite_email"
                            v-model="inviteForm.invited_email"
                            type="email"
                            :placeholder="
                                t('demo.invitations.email_placeholder')
                            "
                            required
                        />
                        <InputError
                            :message="inviteForm.errors.invited_email"
                        />
                    </div>
                    <Button type="submit" :disabled="inviteForm.processing">
                        {{ t('demo.invitations.send_button') }}
                    </Button>
                </form>

                <div
                    v-if="member.invitations && member.invitations.length > 0"
                    class="mt-5 space-y-2 border-t border-border/60 pt-4"
                >
                    <h3 class="text-sm font-semibold">
                        {{ t('demo.invitations.history_title') }}
                    </h3>
                    <div
                        v-for="inv in member.invitations"
                        :key="inv.id"
                        class="flex flex-col justify-between gap-3 rounded-xl border border-border/70 bg-background p-3.5 text-sm md:flex-row md:items-center"
                    >
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="font-medium">{{
                                    inv.invitedEmail
                                }}</span>
                                <span
                                    class="rounded-full bg-muted px-2 py-0.5 text-xs font-medium"
                                >
                                    {{ statusLabel(inv.status) }}
                                </span>
                                <span
                                    v-if="inv.mailDeliveryStatus"
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
                            </div>
                            <div class="mt-1 text-xs text-muted-foreground">
                                <span v-if="inv.acceptedByEmail">
                                    {{
                                        t('demo.review_queue.accepted_by_label')
                                    }}
                                    {{ inv.acceptedByName }} ({{
                                        inv.acceptedByEmail
                                    }}) ·
                                </span>
                                {{ t('demo.review_queue.expires_at_label') }}
                                {{ inv.expiresAt ?? '—' }}
                            </div>
                            <div
                                v-if="inv.reviewNote"
                                class="mt-1 text-xs text-muted-foreground"
                            >
                                {{ inv.reviewNote }}
                            </div>
                        </div>

                        <div
                            v-if="
                                inv.canApproveOrReject ??
                                inv.status === 'pending_lead_approval'
                            "
                            class="flex flex-wrap items-center gap-2"
                        >
                            <Input
                                v-model="invitationNotes[inv.id]"
                                :placeholder="
                                    t('demo.review_queue.reason_placeholder')
                                "
                                class="h-9 w-48 text-xs"
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
                            <Button
                                v-if="inv.canRevoke"
                                size="sm"
                                variant="ghost"
                                @click="revokeInvitation(inv.id)"
                            >
                                {{ t('demo.review_queue.revoke_invitation') }}
                            </Button>
                        </div>

                        <div
                            v-else-if="
                                inv.canResend ||
                                inv.canRevoke ||
                                inv.status === 'pending_acceptance'
                            "
                            class="flex flex-wrap items-center gap-2"
                        >
                            <Button
                                v-if="
                                    inv.canResend ??
                                    inv.status === 'pending_acceptance'
                                "
                                size="sm"
                                variant="secondary"
                                @click="resendInvitation(inv.id)"
                            >
                                {{ t('demo.invitations.resend_button') }}
                            </Button>
                            <Button
                                v-if="
                                    inv.canRevoke ??
                                    inv.status === 'pending_acceptance'
                                "
                                size="sm"
                                variant="outline"
                                @click="revokeInvitation(inv.id)"
                            >
                                {{ t('demo.review_queue.revoke_invitation') }}
                            </Button>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Service Department Participations & History -->
            <section
                class="rounded-2xl border border-border/80 bg-card p-6 shadow-sm"
            >
                <div class="flex items-center gap-2 text-lg font-semibold">
                    <FolderGit2 class="size-5 text-primary" />
                    <h2>{{ t('demo.department_membership.manage_title') }}</h2>
                </div>
                <p class="mt-1 text-xs text-muted-foreground">
                    {{ t('demo.department_membership.manage_description') }}
                </p>

                <div
                    v-if="
                        pageErrors.department_membership ||
                        pageErrors.starts_on ||
                        pageErrors.ends_on
                    "
                    class="mt-3 space-y-1"
                >
                    <InputError :message="pageErrors.department_membership" />
                    <InputError :message="pageErrors.starts_on" />
                    <InputError :message="pageErrors.ends_on" />
                </div>

                <!-- Assign Department Form (School Lead Only) -->
                <form
                    v-if="
                        member.canManageMember &&
                        availableDepartments &&
                        availableDepartments.length > 0
                    "
                    class="mt-4 grid gap-4 md:grid-cols-5"
                    @submit.prevent="assignDepartment"
                >
                    <div class="grid gap-1.5">
                        <Label for="dm_unit_id">{{
                            t('demo.department_membership.department_label')
                        }}</Label>
                        <select
                            id="dm_unit_id"
                            v-model="deptAssignForm.organizational_unit_id"
                            class="h-10 rounded-md border border-input bg-background px-3 text-sm"
                        >
                            <option
                                v-for="dept in availableDepartments"
                                :key="dept.id"
                                :value="dept.id"
                            >
                                {{ localizedText(dept.nameAm, dept.nameEn) }}
                            </option>
                        </select>
                        <InputError
                            :message="
                                deptAssignForm.errors.organizational_unit_id
                            "
                        />
                    </div>

                    <div class="grid gap-1.5">
                        <Label for="dm_role">{{
                            t('demo.department_membership.role_label')
                        }}</Label>
                        <Input
                            id="dm_role"
                            v-model="deptAssignForm.role_label_am"
                        />
                    </div>

                    <div class="grid gap-1.5">
                        <Label for="dm_starts_on">{{
                            t('demo.department_membership.starts_on_label')
                        }}</Label>
                        <Input
                            id="dm_starts_on"
                            v-model="deptAssignForm.starts_on"
                            type="date"
                            required
                        />
                        <InputError
                            :message="deptAssignForm.errors.starts_on"
                        />
                    </div>

                    <div class="grid gap-1.5">
                        <Label for="dm_ends_on">{{
                            t('demo.department_membership.ends_on_label')
                        }}</Label>
                        <Input
                            id="dm_ends_on"
                            v-model="deptAssignForm.ends_on"
                            type="date"
                        />
                        <InputError :message="deptAssignForm.errors.ends_on" />
                    </div>

                    <div class="flex items-end">
                        <Button
                            type="submit"
                            :disabled="deptAssignForm.processing"
                        >
                            {{ t('demo.department_membership.assign_button') }}
                        </Button>
                    </div>
                </form>

                <!-- Participation History List -->
                <div
                    v-if="
                        member.departmentParticipations &&
                        member.departmentParticipations.length > 0
                    "
                    class="mt-5 space-y-2 border-t border-border/60 pt-4"
                >
                    <div
                        v-for="dm in member.departmentParticipations"
                        :key="dm.id"
                        class="flex flex-col justify-between gap-3 rounded-xl border border-border/70 bg-background p-3.5 text-sm md:flex-row md:items-center"
                    >
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="font-semibold">{{
                                    localizedText(dm.nameAm, dm.nameEn)
                                }}</span>
                                <span
                                    v-if="
                                        dm.isActive &&
                                        dm.isCurrentlyEffective === false
                                    "
                                    class="rounded-full bg-amber-500/15 px-2 py-0.5 text-xs font-medium text-amber-700 dark:text-amber-300"
                                >
                                    {{
                                        t(
                                            'demo.department_membership.scheduled_future_badge',
                                            { date: dm.startsOn ?? '—' },
                                        )
                                    }}
                                </span>
                                <span
                                    v-else
                                    class="rounded-full px-2 py-0.5 text-xs font-medium"
                                    :class="
                                        dm.isActive
                                            ? 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300'
                                            : 'bg-muted text-muted-foreground'
                                    "
                                >
                                    {{
                                        dm.isActive
                                            ? t(
                                                  'demo.department_membership.active_badge',
                                              )
                                            : t(
                                                  'demo.department_membership.ended_badge',
                                                  { date: dm.endsOn ?? '—' },
                                              )
                                    }}
                                </span>
                            </div>
                            <div class="mt-1 text-xs text-muted-foreground">
                                {{
                                    dm.roleLabelAm ??
                                    t('common.member_default_role')
                                }}
                                ·
                                {{
                                    t('demo.lead_appointments.period', {
                                        start: dm.startsOn ?? '—',
                                        end:
                                            dm.endsOn ??
                                            t(
                                                'demo.lead_appointments.open_ended',
                                            ),
                                    })
                                }}
                            </div>
                        </div>

                        <div
                            v-if="member.canManageMember"
                            class="flex flex-wrap items-center gap-2"
                        >
                            <template v-if="dm.isActive">
                                <Input
                                    v-model="dmActionDates[dm.id]"
                                    type="date"
                                    class="h-9 w-36 text-xs"
                                />
                                <Button
                                    size="sm"
                                    variant="outline"
                                    @click="endDepartmentParticipation(dm.id)"
                                >
                                    {{
                                        t(
                                            'demo.department_membership.end_button',
                                        )
                                    }}
                                </Button>
                            </template>
                            <template
                                v-else-if="dm.canStartNewPeriod !== false"
                            >
                                <Input
                                    v-model="dmActionDates[dm.id]"
                                    type="date"
                                    class="h-9 w-36 text-xs"
                                />
                                <Button
                                    size="sm"
                                    variant="outline"
                                    @click="
                                        reactivateDepartmentParticipation(dm.id)
                                    "
                                >
                                    {{
                                        t(
                                            'demo.department_membership.new_period_button',
                                        )
                                    }}
                                </Button>
                            </template>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Membership & Account-Link Decision History -->
            <section
                v-if="member.decisions && member.decisions.length > 0"
                class="rounded-2xl border border-border/80 bg-card p-6 shadow-sm"
            >
                <div class="flex items-center gap-2 text-lg font-semibold">
                    <History class="size-5 text-primary" />
                    <h2>{{ t('demo.decisions.title') }}</h2>
                </div>
                <div class="mt-4 overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead
                            class="border-b text-xs text-muted-foreground uppercase"
                        >
                            <tr>
                                <th class="py-2 pr-4">
                                    {{ t('demo.decisions.col_date') }}
                                </th>
                                <th class="py-2 pr-4">
                                    {{ t('demo.decisions.col_action') }}
                                </th>
                                <th class="py-2 pr-4">
                                    {{ t('demo.decisions.col_transition') }}
                                </th>
                                <th class="py-2 pr-4">
                                    {{ t('demo.decisions.col_actor') }}
                                </th>
                                <th class="py-2">
                                    {{ t('demo.decisions.col_reason') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <tr v-for="dec in member.decisions" :key="dec.id">
                                <td class="py-2.5 pr-4 text-xs">
                                    {{ dec.createdAt ?? '—' }}
                                </td>
                                <td class="py-2.5 pr-4 font-medium">
                                    {{ decisionActionLabel(dec.action) }}
                                </td>
                                <td class="py-2.5 pr-4 text-xs">
                                    <span v-if="dec.fromStatus">
                                        {{ statusLabel(dec.fromStatus) }} →
                                    </span>
                                    {{ statusLabel(dec.toStatus) }}
                                </td>
                                <td class="py-2.5 pr-4 text-xs">
                                    {{ dec.actorName ?? '—' }}
                                </td>
                                <td class="py-2.5 text-xs">
                                    {{ dec.reason ?? '—' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </PageContainer>
    </AppLayout>
</template>
