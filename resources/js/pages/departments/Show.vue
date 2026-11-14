<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { FileSpreadsheet, Users } from 'lucide-vue-next';
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
    department: {
        id: number;
        code: string;
        categoryCode: string;
        nameAm: string;
        nameEn: string | null;
        schoolId: number;
        schoolNameAm: string | null;
        schoolNameEn: string | null;
        parishNameAm: string | null;
        parishNameEn?: string | null;
        cityAm: string | null;
        cityEn?: string | null;
    };
    members: {
        id: number;
        membershipCode: string | null;
        fullName: string;
        nameEn: string | null;
        roleLabelAm: string | null;
        startsOn: string | null;
        allDepartments: string[];
    }[];
    reports: {
        id: number;
        periodLabel: string;
        titleAm: string;
        activityCount: number;
        status: string;
        canSubmitLocal: boolean;
        canReleaseUpward: boolean;
    }[];
};

const props = defineProps<Props>();

const departmentDisplayName = computed(() =>
    localizedText(props.department.nameAm, props.department.nameEn),
);

const schoolDisplayName = computed(() =>
    localizedText(props.department.schoolNameAm, props.department.schoolNameEn),
);

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: t('nav.dashboard'), href: '/dashboard' },
    {
        title: schoolDisplayName.value,
        href: `/schools/${props.department.schoolId}`,
    },
    {
        title: departmentDisplayName.value,
        href: `/departments/${props.department.id}`,
    },
]);

const submitReportLocal = (reportId: number): void => {
    router.post(
        `/reports/${reportId}/submit-local`,
        {},
        { preserveScroll: true },
    );
};

const releaseReportUpward = (reportId: number): void => {
    router.post(`/reports/${reportId}/release`, {}, { preserveScroll: true });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="departmentDisplayName" />
        <PageContainer>
            <PageHeader
                :title="departmentDisplayName"
                :description="
                    t('demo.department.header_meta', {
                        school: schoolDisplayName,
                        parish: localizedText(
                            department.parishNameAm,
                            department.parishNameEn,
                        ),
                        category: categoryLabel(department.categoryCode),
                    })
                "
            />

            <!-- Department Members -->
            <section
                class="rounded-2xl border border-border/80 bg-card p-6 shadow-sm"
            >
                <div class="flex items-center gap-2 text-lg font-semibold">
                    <Users class="size-5 text-primary" />
                    <h2>{{ t('demo.department.members_title') }}</h2>
                </div>
                <p class="mt-1 text-xs text-muted-foreground">
                    {{ t('demo.department.members_description') }}
                </p>

                <div class="mt-4 overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead
                            class="border-b text-xs text-muted-foreground uppercase"
                        >
                            <tr>
                                <th class="py-2 pr-4">
                                    {{ t('demo.department.col_full_name') }}
                                </th>
                                <th class="py-2 pr-4">
                                    {{ t('demo.department.col_role') }}
                                </th>
                                <th class="py-2 pr-4">
                                    {{ t('demo.department.col_starts_on') }}
                                </th>
                                <th class="py-2">
                                    {{
                                        t('demo.department.col_all_departments')
                                    }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <tr v-for="m in members" :key="m.id">
                                <td class="py-3 pr-4 font-medium">
                                    <Link
                                        :href="`/members/${m.id}`"
                                        class="underline hover:text-primary"
                                    >
                                        {{ m.fullName }}
                                    </Link>
                                    <div
                                        v-if="m.nameEn"
                                        class="text-xs text-muted-foreground"
                                    >
                                        {{ m.nameEn }}
                                    </div>
                                </td>
                                <td class="py-3 pr-4 text-xs">
                                    {{
                                        m.roleLabelAm ??
                                        t('common.member_default_role')
                                    }}
                                </td>
                                <td class="py-3 pr-4 text-xs">
                                    {{ m.startsOn ?? '—' }}
                                </td>
                                <td class="py-3 text-xs">
                                    {{ m.allDepartments.join(' · ') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Department Reports -->
            <section
                class="rounded-2xl border border-border/80 bg-card p-6 shadow-sm"
            >
                <div class="flex items-center gap-2 text-lg font-semibold">
                    <FileSpreadsheet class="size-5 text-primary" />
                    <h2>{{ t('demo.department.reports_title') }}</h2>
                </div>
                <p class="mt-1 text-xs text-muted-foreground">
                    {{ t('demo.department.reports_description') }}
                </p>

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
                                <span class="text-xs text-muted-foreground">{{
                                    rep.periodLabel
                                }}</span>
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
                                v-if="rep.canSubmitLocal"
                                size="sm"
                                variant="default"
                                @click="submitReportLocal(rep.id)"
                            >
                                {{ t('demo.department.submit_to_lead_button') }}
                            </Button>
                            <Button
                                v-if="rep.canReleaseUpward"
                                size="sm"
                                variant="default"
                                @click="releaseReportUpward(rep.id)"
                            >
                                {{
                                    t('demo.department.approve_release_button')
                                }}
                            </Button>
                            <Button size="sm" variant="outline" as-child>
                                <Link :href="`/reports/${rep.id}`">{{
                                    t('common.view_details')
                                }}</Link>
                            </Button>
                        </div>
                    </div>
                </div>
            </section>
        </PageContainer>
    </AppLayout>
</template>
