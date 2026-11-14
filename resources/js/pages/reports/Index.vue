<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { AlertTriangle, CheckCircle2, FileSpreadsheet } from 'lucide-vue-next';
import { computed } from 'vue';
import PageContainer from '@/components/PageContainer.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
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

type Props = {
    reports: {
        id: number;
        schoolId: number;
        schoolNameAm: string;
        schoolNameEn: string | null;
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
    completeness: {
        schoolId: number;
        schoolNameAm: string;
        schoolNameEn: string | null;
        districtNameAm: string | null;
        districtNameEn?: string | null;
        cityAm: string | null;
        cityEn?: string | null;
        releasedReportsCount: number;
        completenessState: 'submitted_released' | 'missing_submission';
    }[];
    nationalSummary?: {
        diocesesCount: number;
        districtsCount: number;
        schoolsCount: number;
        approvedMembersTotal: number;
        releasedReportsTotal: number;
        reportingSchoolsCount: number;
        missingSchoolsCount: number;
    } | null;
};

defineProps<Props>();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: t('nav.dashboard'), href: '/dashboard' },
    { title: t('nav.reports'), href: '/reports' },
]);

const submitLocal = (id: number): void => {
    router.post(`/reports/${id}/submit-local`, {}, { preserveScroll: true });
};

const releaseUpward = (id: number): void => {
    router.post(`/reports/${id}/release`, {}, { preserveScroll: true });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="t('nav.reports')" />
        <PageContainer>
            <PageHeader
                :title="t('demo.reports.title')"
                :description="t('demo.reports.description')"
            />

            <!-- National Hierarchy & Reporting Summary (Union Super Admin / Governing Board) -->
            <section
                v-if="nationalSummary"
                class="rounded-2xl border border-border/80 bg-card p-6 shadow-sm"
            >
                <h2 class="text-lg font-semibold">
                    {{ t('demo.national_summary.title') }}
                </h2>
                <p class="mt-1 text-xs text-muted-foreground">
                    {{ t('demo.national_summary.description') }}
                </p>

                <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <div
                        class="rounded-xl border border-border/70 bg-background p-4"
                    >
                        <div class="text-xs text-muted-foreground">
                            {{ t('demo.national_summary.dioceses_count') }} /
                            {{ t('demo.national_summary.districts_count') }}
                        </div>
                        <div class="mt-1 text-2xl font-bold">
                            {{ nationalSummary.diocesesCount }} /
                            {{ nationalSummary.districtsCount }}
                        </div>
                    </div>
                    <div
                        class="rounded-xl border border-border/70 bg-background p-4"
                    >
                        <div class="text-xs text-muted-foreground">
                            {{ t('demo.national_summary.schools_count') }}
                        </div>
                        <div class="mt-1 text-2xl font-bold">
                            {{ nationalSummary.schoolsCount }}
                        </div>
                    </div>
                    <div
                        class="rounded-xl border border-border/70 bg-background p-4"
                    >
                        <div class="text-xs text-muted-foreground">
                            {{
                                t(
                                    'demo.national_summary.approved_members_total',
                                )
                            }}
                        </div>
                        <div class="mt-1 text-2xl font-bold">
                            {{ nationalSummary.approvedMembersTotal }}
                        </div>
                    </div>
                    <div
                        class="rounded-xl border border-border/70 bg-background p-4"
                    >
                        <div class="text-xs text-muted-foreground">
                            {{
                                t(
                                    'demo.national_summary.released_reports_total',
                                )
                            }}
                        </div>
                        <div class="mt-1 text-2xl font-bold">
                            {{ nationalSummary.releasedReportsTotal }}
                            <span
                                class="text-xs font-normal text-muted-foreground"
                            >
                                ({{
                                    t(
                                        'demo.national_summary.reporting_schools',
                                    )
                                }}: {{ nationalSummary.reportingSchoolsCount }}
                                ·
                                {{
                                    t('demo.national_summary.missing_schools')
                                }}: {{ nationalSummary.missingSchoolsCount }})
                            </span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Reporting Completeness Overview for Higher Offices -->
            <section
                v-if="completeness.length > 0"
                class="rounded-2xl border border-border/80 bg-card p-6 shadow-sm"
            >
                <h2 class="text-lg font-semibold">
                    {{ t('demo.reports.completeness_title') }}
                </h2>
                <p class="mt-1 text-xs text-muted-foreground">
                    {{ t('demo.reports.completeness_description') }}
                </p>

                <div class="mt-4 overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead
                            class="border-b text-xs text-muted-foreground uppercase"
                        >
                            <tr>
                                <th class="py-2 pr-4">
                                    {{ t('demo.reports.col_school') }}
                                </th>
                                <th class="py-2 pr-4">
                                    {{ t('demo.reports.col_district_city') }}
                                </th>
                                <th class="py-2 pr-4">
                                    {{ t('demo.reports.col_released_count') }}
                                </th>
                                <th class="py-2">
                                    {{ t('demo.reports.col_completeness') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <tr v-for="row in completeness" :key="row.schoolId">
                                <td class="py-3 pr-4 font-medium">
                                    {{
                                        localizedText(
                                            row.schoolNameAm,
                                            row.schoolNameEn,
                                        )
                                    }}
                                </td>
                                <td class="py-3 pr-4 text-xs">
                                    {{
                                        localizedText(
                                            row.districtNameAm,
                                            row.districtNameEn,
                                        )
                                    }}
                                    ({{
                                        localizedText(row.cityAm, row.cityEn)
                                    }})
                                </td>
                                <td class="py-3 pr-4 text-xs">
                                    {{ row.releasedReportsCount }}
                                </td>
                                <td class="py-3">
                                    <span
                                        v-if="
                                            row.completenessState ===
                                            'submitted_released'
                                        "
                                        class="inline-flex items-center gap-1 rounded-full bg-emerald-500/15 px-2.5 py-0.5 text-xs font-medium text-emerald-700 dark:text-emerald-300"
                                    >
                                        <CheckCircle2 class="size-3.5" />
                                        {{ statusLabel(row.completenessState) }}
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center gap-1 rounded-full bg-amber-500/15 px-2.5 py-0.5 text-xs font-medium text-amber-700 dark:text-amber-300"
                                    >
                                        <AlertTriangle class="size-3.5" />
                                        {{ statusLabel(row.completenessState) }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Visible Reports List -->
            <section
                class="rounded-2xl border border-border/80 bg-card p-6 shadow-sm"
            >
                <div class="flex items-center gap-2 text-lg font-semibold">
                    <FileSpreadsheet class="size-5 text-primary" />
                    <h2>{{ t('demo.reports.authorized_reports_title') }}</h2>
                </div>

                <div class="mt-4 space-y-3">
                    <div
                        v-for="rep in reports"
                        :key="rep.id"
                        class="flex flex-col justify-between gap-3 rounded-xl border border-border/70 bg-background p-4 md:flex-row md:items-center"
                    >
                        <div class="space-y-1">
                            <div class="flex flex-wrap items-center gap-2">
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
                                <span
                                    class="font-mono text-xs text-muted-foreground"
                                    >#{{ rep.id }}</span
                                >
                                <span class="text-xs text-muted-foreground">
                                    {{
                                        localizedText(
                                            rep.schoolNameAm,
                                            rep.schoolNameEn,
                                        )
                                    }}
                                    ·
                                    {{
                                        rep.unitNameAm
                                            ? localizedText(
                                                  rep.unitNameAm,
                                                  rep.unitNameEn,
                                              )
                                            : categoryLabel(rep.categoryCode)
                                    }}
                                    ·
                                    {{ rep.periodLabel }}
                                </span>
                            </div>
                            <Link
                                :href="`/reports/${rep.id}`"
                                class="block font-semibold underline hover:text-primary"
                            >
                                {{ rep.titleAm }}
                            </Link>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            <Button
                                v-if="rep.canSubmitLocal"
                                size="sm"
                                @click="submitLocal(rep.id)"
                            >
                                {{ t('demo.reports.submit_to_lead_button') }}
                            </Button>
                            <Button
                                v-if="rep.canReleaseUpward"
                                size="sm"
                                @click="releaseUpward(rep.id)"
                            >
                                {{ t('demo.reports.approve_release_button') }}
                            </Button>
                            <Button size="sm" variant="outline" as-child>
                                <Link :href="`/reports/${rep.id}`">{{
                                    t('demo.reports.open_details_button')
                                }}</Link>
                            </Button>
                        </div>
                    </div>

                    <p
                        v-if="reports.length === 0"
                        class="text-sm text-muted-foreground"
                    >
                        {{ t('demo.reports.no_reports') }}
                    </p>
                </div>
            </section>
        </PageContainer>
    </AppLayout>
</template>
