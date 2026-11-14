<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import {
    Archive,
    Ban,
    CheckCircle2,
    Clock,
    History,
    XCircle,
} from 'lucide-vue-next';
import { computed } from 'vue';
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

type Props = {
    membership: {
        id: number;
        membershipCode: string | null;
        status: string;
        rawStatus?: string;
        accountLinkStatus?: string;
        isPendingLinkOnly?: boolean;
        statusReason?: string | null;
        schoolNameAm: string | null;
        schoolNameEn: string | null;
        parishNameAm: string | null;
        parishNameEn?: string | null;
        cityAm: string | null;
        cityEn?: string | null;
        applicationNote: string | null;
        joinedOn: string | null;
        endedOn?: string | null;
        approvedAt: string | null;
        fullName: string;
        nameEn: string;
        phone: string;
        serviceNotes: string;
        canEditOwnProfile: boolean;
        departments: {
            id: number;
            nameAm: string;
            nameEn: string | null;
            roleLabelAm: string | null;
        }[];
        decisions?: {
            id: number;
            action: string;
            fromStatus: string | null;
            toStatus: string;
            reason: string | null;
            actorName: string | null;
            createdAt: string | null;
        }[];
    } | null;
};

const props = defineProps<Props>();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: t('nav.dashboard'), href: '/dashboard' },
    { title: t('nav.my_profile'), href: '/my-profile' },
]);

const form = useForm({
    full_name: props.membership?.fullName ?? '',
    name_en: props.membership?.nameEn ?? '',
    phone: props.membership?.phone ?? '',
    service_notes: props.membership?.serviceNotes ?? '',
});

const submitProfile = (): void => {
    form.put('/my-profile', {
        preserveScroll: true,
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="t('nav.my_profile')" />
        <PageContainer>
            <PageHeader
                :title="t('demo.my_profile.title')"
                :description="t('demo.my_profile.description')"
            />

            <template v-if="membership">
                <!-- Status Card -->
                <section
                    class="rounded-2xl border border-border/80 bg-card p-6 shadow-sm"
                >
                    <div
                        class="flex flex-wrap items-center justify-between gap-4"
                    >
                        <div>
                            <div class="text-xs text-muted-foreground">
                                {{ t('demo.my_profile.school_label') }}
                            </div>
                            <h2 class="text-lg font-bold">
                                {{
                                    localizedText(
                                        membership.schoolNameAm,
                                        membership.schoolNameEn,
                                    )
                                }}
                            </h2>
                            <div class="mt-1 text-xs text-muted-foreground">
                                {{
                                    t('demo.my_profile.parish_city_meta', {
                                        parish: localizedText(
                                            membership.parishNameAm,
                                            membership.parishNameEn,
                                        ),
                                        city: localizedText(
                                            membership.cityAm,
                                            membership.cityEn,
                                        ),
                                    })
                                }}
                            </div>
                        </div>

                        <div>
                            <span
                                v-if="membership.status === 'approved'"
                                class="inline-flex items-center gap-1.5 rounded-full bg-emerald-500/15 px-3 py-1 text-xs font-semibold text-emerald-700 dark:text-emerald-300"
                            >
                                <CheckCircle2 class="size-4" />
                                {{ statusLabel(membership.status) }}
                            </span>
                            <span
                                v-else-if="membership.status === 'pending'"
                                class="inline-flex items-center gap-1.5 rounded-full bg-amber-500/15 px-3 py-1 text-xs font-semibold text-amber-700 dark:text-amber-300"
                            >
                                <Clock class="size-4" />
                                {{ statusLabel(membership.status) }}
                            </span>
                            <span
                                v-else-if="membership.status === 'suspended'"
                                class="inline-flex items-center gap-1.5 rounded-full bg-rose-500/15 px-3 py-1 text-xs font-semibold text-rose-700 dark:text-rose-300"
                            >
                                <Ban class="size-4" />
                                {{ statusLabel(membership.status) }}
                            </span>
                            <span
                                v-else-if="membership.status === 'rejected'"
                                class="inline-flex items-center gap-1.5 rounded-full bg-destructive/15 px-3 py-1 text-xs font-semibold text-destructive"
                            >
                                <XCircle class="size-4" />
                                {{ statusLabel(membership.status) }}
                            </span>
                            <span
                                v-else
                                class="inline-flex items-center gap-1.5 rounded-full bg-muted px-3 py-1 text-xs font-semibold text-muted-foreground"
                            >
                                <Archive class="size-4" />
                                {{ statusLabel(membership.status) }}
                            </span>
                        </div>
                    </div>

                    <!-- Pending Account Link Banner -->
                    <div
                        v-if="membership.isPendingLinkOnly"
                        class="mt-4 rounded-xl border border-amber-500/30 bg-amber-500/5 p-4 text-sm"
                    >
                        <p
                            class="font-medium text-amber-800 dark:text-amber-300"
                        >
                            {{ t('demo.my_profile.pending_link_banner_title') }}
                        </p>
                        <p class="mt-1 text-xs text-muted-foreground">
                            {{ t('demo.my_profile.pending_link_banner_body') }}
                        </p>
                    </div>

                    <!-- Pending Membership Banner -->
                    <div
                        v-else-if="membership.status === 'pending'"
                        class="mt-4 rounded-xl border border-amber-500/30 bg-amber-500/5 p-4 text-sm"
                    >
                        <p
                            class="font-medium text-amber-800 dark:text-amber-300"
                        >
                            {{ t('demo.my_profile.pending_banner_title') }}
                        </p>
                        <p class="mt-1 text-xs text-muted-foreground">
                            {{
                                t('demo.my_profile.pending_banner_body', {
                                    note:
                                        membership.applicationNote ??
                                        membership.statusReason ??
                                        '—',
                                })
                            }}
                        </p>
                    </div>

                    <!-- Rejected Membership Banner -->
                    <div
                        v-else-if="membership.status === 'rejected'"
                        class="mt-4 rounded-xl border border-destructive/30 bg-destructive/10 p-4 text-sm"
                    >
                        <p class="font-medium text-destructive">
                            {{ t('demo.my_profile.rejected_banner_title') }}
                        </p>
                        <p class="mt-1 text-xs text-muted-foreground">
                            {{
                                t('demo.my_profile.rejected_banner_body', {
                                    reason: membership.statusReason ?? '—',
                                })
                            }}
                        </p>
                    </div>

                    <!-- Suspended Membership Banner -->
                    <div
                        v-else-if="membership.status === 'suspended'"
                        class="mt-4 rounded-xl border border-rose-500/30 bg-rose-500/10 p-4 text-sm"
                    >
                        <p class="font-medium text-rose-700 dark:text-rose-300">
                            {{ t('demo.my_profile.suspended_banner_title') }}
                        </p>
                        <p class="mt-1 text-xs text-muted-foreground">
                            {{
                                t('demo.my_profile.suspended_banner_body', {
                                    reason: membership.statusReason ?? '—',
                                })
                            }}
                        </p>
                    </div>

                    <!-- Archived Membership Banner -->
                    <div
                        v-else-if="membership.status === 'archived'"
                        class="mt-4 rounded-xl border border-border bg-muted/40 p-4 text-sm"
                    >
                        <p class="font-medium text-foreground">
                            {{ t('demo.my_profile.archived_banner_title') }}
                        </p>
                        <p class="mt-1 text-xs text-muted-foreground">
                            {{
                                t('demo.my_profile.archived_banner_body', {
                                    reason: membership.statusReason ?? '—',
                                })
                            }}
                        </p>
                    </div>

                    <div
                        v-if="membership.departments.length > 0"
                        class="mt-4 border-t border-border/60 pt-4"
                    >
                        <div
                            class="text-xs font-semibold text-muted-foreground"
                        >
                            {{
                                t('demo.my_profile.assigned_departments_label')
                            }}
                        </div>
                        <div class="mt-2 flex flex-wrap gap-2">
                            <span
                                v-for="dept in membership.departments"
                                :key="dept.id"
                                class="rounded-lg border bg-background px-3 py-1 text-xs font-medium"
                            >
                                {{ localizedText(dept.nameAm, dept.nameEn) }} —
                                {{
                                    dept.roleLabelAm ??
                                    t('common.member_default_role')
                                }}
                            </span>
                        </div>
                    </div>
                </section>

                <!-- Self-Service Profile Form (Only for Approved Members) -->
                <section
                    v-if="membership.canEditOwnProfile"
                    class="rounded-2xl border border-border/80 bg-card p-6 shadow-sm"
                >
                    <h2 class="text-lg font-semibold">
                        {{ t('demo.my_profile.edit_section_title') }}
                    </h2>
                    <p class="mt-1 text-xs text-muted-foreground">
                        {{ t('demo.my_profile.edit_section_description') }}
                    </p>

                    <form
                        class="mt-4 grid gap-4 md:grid-cols-2"
                        @submit.prevent="submitProfile"
                    >
                        <div class="grid gap-1.5">
                            <Label for="full_name">{{
                                t('demo.my_profile.full_name_label')
                            }}</Label>
                            <Input
                                id="full_name"
                                v-model="form.full_name"
                                required
                            />
                            <InputError :message="form.errors.full_name" />
                        </div>

                        <div class="grid gap-1.5">
                            <Label for="name_en">{{
                                t('demo.my_profile.name_en_label')
                            }}</Label>
                            <Input id="name_en" v-model="form.name_en" />
                            <InputError :message="form.errors.name_en" />
                        </div>

                        <div class="grid gap-1.5">
                            <Label for="phone">{{
                                t('demo.my_profile.phone_label')
                            }}</Label>
                            <Input id="phone" v-model="form.phone" />
                            <InputError :message="form.errors.phone" />
                        </div>

                        <div class="grid gap-1.5 md:col-span-2">
                            <Label for="service_notes">{{
                                t('demo.my_profile.service_notes_label')
                            }}</Label>
                            <Input
                                id="service_notes"
                                v-model="form.service_notes"
                            />
                            <InputError :message="form.errors.service_notes" />
                        </div>

                        <div class="md:col-span-2">
                            <Button type="submit" :disabled="form.processing">
                                {{ t('demo.my_profile.save_button') }}
                            </Button>
                        </div>
                    </form>
                </section>

                <!-- Member Decision History -->
                <section
                    v-if="
                        membership.decisions && membership.decisions.length > 0
                    "
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
                                    <th class="py-2">
                                        {{ t('demo.decisions.col_reason') }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <tr
                                    v-for="dec in membership.decisions"
                                    :key="dec.id"
                                >
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
                                    <td class="py-2.5 text-xs">
                                        {{ dec.reason ?? '—' }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>
            </template>

            <section
                v-else
                class="rounded-2xl border border-border/80 bg-card p-6 text-sm text-muted-foreground"
            >
                {{ t('demo.my_profile.unlinked_notice') }}
            </section>
        </PageContainer>
    </AppLayout>
</template>
