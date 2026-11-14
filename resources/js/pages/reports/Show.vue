<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
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
    report: {
        id: number;
        schoolId: number;
        schoolNameAm: string;
        schoolNameEn: string | null;
        districtNameAm: string | null;
        districtNameEn?: string | null;
        dioceseNameAm: string | null;
        dioceseNameEn?: string | null;
        unitNameAm: string | null;
        unitNameEn?: string | null;
        categoryCode: string;
        periodLabel: string;
        titleAm: string;
        summaryAm: string;
        activityCount: number;
        status: string;
        submittedBy: string | null;
        approvedBy: string | null;
        releasedAt: string | null;
        canSubmitLocal: boolean;
        canReleaseUpward: boolean;
    };
};

const props = defineProps<Props>();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: t('nav.dashboard'), href: '/dashboard' },
    { title: t('nav.reports'), href: '/reports' },
    {
        title: `#${props.report.id} ${props.report.titleAm}`,
        href: `/reports/${props.report.id}`,
    },
]);

const submitLocal = (): void => {
    router.post(
        `/reports/${props.report.id}/submit-local`,
        {},
        { preserveScroll: true },
    );
};

const releaseUpward = (): void => {
    router.post(
        `/reports/${props.report.id}/release`,
        {},
        { preserveScroll: true },
    );
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="report.titleAm" />
        <PageContainer>
            <PageHeader
                :title="report.titleAm"
                :description="
                    t('demo.reports.show_meta', {
                        school: localizedText(
                            report.schoolNameAm,
                            report.schoolNameEn,
                        ),
                        unit: report.unitNameAm
                            ? localizedText(
                                  report.unitNameAm,
                                  report.unitNameEn,
                              )
                            : categoryLabel(report.categoryCode),
                        period: report.periodLabel,
                    })
                "
            >
                <template #actions>
                    <div class="flex gap-2">
                        <Button
                            v-if="report.canSubmitLocal"
                            @click="submitLocal"
                        >
                            {{ t('demo.reports.submit_to_lead_button') }}
                        </Button>
                        <Button
                            v-if="report.canReleaseUpward"
                            @click="releaseUpward"
                        >
                            {{ t('demo.reports.approve_release_button') }}
                        </Button>
                    </div>
                </template>
            </PageHeader>

            <section
                class="grid gap-4 rounded-2xl border border-border/80 bg-card p-6 shadow-sm md:grid-cols-3"
            >
                <div>
                    <div class="text-xs text-muted-foreground">
                        {{ t('demo.reports.status_label') }}
                    </div>
                    <div class="mt-1 font-semibold">
                        {{ statusLabel(report.status) }}
                    </div>
                </div>
                <div>
                    <div class="text-xs text-muted-foreground">
                        {{ t('demo.reports.category_label') }}
                    </div>
                    <div class="mt-1 text-sm font-medium">
                        {{ categoryLabel(report.categoryCode) }}
                        <span class="font-mono text-xs text-muted-foreground"
                            >({{ report.categoryCode }})</span
                        >
                    </div>
                </div>
                <div>
                    <div class="text-xs text-muted-foreground">
                        {{ t('demo.reports.activity_count_label') }}
                    </div>
                    <div class="mt-1 font-semibold">
                        {{ report.activityCount }}
                    </div>
                </div>
                <div>
                    <div class="text-xs text-muted-foreground">
                        {{ t('demo.reports.district_diocese_label') }}
                    </div>
                    <div class="mt-1 text-sm">
                        {{
                            localizedText(
                                report.districtNameAm,
                                report.districtNameEn,
                            )
                        }}
                        /
                        {{
                            localizedText(
                                report.dioceseNameAm,
                                report.dioceseNameEn,
                            )
                        }}
                    </div>
                </div>
                <div>
                    <div class="text-xs text-muted-foreground">
                        {{ t('demo.reports.submitted_by_label') }}
                    </div>
                    <div class="mt-1 text-sm">
                        {{ report.submittedBy ?? '—' }}
                    </div>
                </div>
                <div>
                    <div class="text-xs text-muted-foreground">
                        {{ t('demo.reports.approved_by_label') }}
                    </div>
                    <div class="mt-1 text-sm">
                        {{
                            report.approvedBy ??
                            t('demo.reports.not_yet_released')
                        }}
                        ({{ report.releasedAt ?? '—' }})
                    </div>
                </div>
                <div class="border-t border-border/60 pt-4 md:col-span-3">
                    <div class="text-xs font-semibold text-muted-foreground">
                        {{ t('demo.reports.summary_label') }}
                    </div>
                    <p class="mt-2 text-sm leading-6 whitespace-pre-wrap">
                        {{ report.summaryAm }}
                    </p>
                </div>
            </section>
        </PageContainer>
    </AppLayout>
</template>
