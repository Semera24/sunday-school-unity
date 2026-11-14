<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    Building2,
    CheckCircle2,
    FileOutput,
    FileSpreadsheet,
    FolderOpen,
    GitBranch,
    Lock,
    ScrollText,
    Settings2,
    Shield,
    UserCheck,
    Users,
} from 'lucide-vue-next';
import { computed } from 'vue';
import RecentActivityPanel from '@/components/admin/RecentActivityPanel.vue';
import StatCard from '@/components/admin/StatCard.vue';
import PageContainer from '@/components/PageContainer.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { useT } from '@/composables/useT';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { index as activityLogsIndex } from '@/routes/activity-logs';
import { edit as adminSettingsEdit } from '@/routes/admin-settings';
import { index as exportsIndex } from '@/routes/exports';
import { index as mediaIndex } from '@/routes/media';
import { index as rolesIndex } from '@/routes/roles';
import { index as usersIndex } from '@/routes/users';
import type { Auth, BreadcrumbItem } from '@/types';

type Metric = {
    key: string;
    href: string;
    label: string;
    value: number;
    description: string;
    tone: 'amber' | 'sky' | 'emerald' | 'violet';
};

type ActivityItem = {
    id: number;
    event: string;
    description: string;
    createdAt: string | null;
};

type ScopeOption = {
    key: string;
    kind: string;
    roleKey: string;
    title: string;
    organizationId: number | null;
    organizationName: string;
    unitName?: string | null;
};

type ScopeSummary = {
    active: {
        key: string;
        kind: string;
        roleKey: string;
        title: string;
        titleAm: string;
        titleEn: string;
        organizationId: number | null;
        organizationName: string;
        organizationNameAm: string;
        organizationNameEn: string;
        organizationType: string;
        unitId: number | null;
        unitName?: string | null;
        functionalCategory?: string | null;
    } | null;
    available: ScopeOption[];
    capabilities: {
        permissionCodes?: string[];
        viewHierarchy: boolean;
        viewReports: boolean;
        viewOwnSchool: number | null;
        viewOwnDepartment: number | null;
        viewMyProfile: boolean;
        isPendingOrUnlinked: boolean;
        isApprovedMember: boolean;
        isUnionSuperAdmin?: boolean;
        isTechnicalOperator: boolean;
    };
};

type DemoOverview = {
    roleKey: string;
    organization: {
        id: number;
        code: string;
        type: string;
        nameAm: string;
        nameEn: string | null;
        cityAm: string | null;
        cityEn?: string | null;
        parishNameAm: string | null;
        parishNameEn?: string | null;
        schoolHref: string | null;
    } | null;
    unit: {
        id: number;
        code: string;
        unitKind: string;
        nameAm: string;
        nameEn: string | null;
        departmentHref: string | null;
    } | null;
    visibleReportsCount: number;
    pendingMembersCount: number;
    jurisdictionSchools: {
        id: number;
        nameAm: string;
        nameEn: string | null;
        parishNameAm: string | null;
        parishNameEn?: string | null;
        cityAm: string | null;
        cityEn?: string | null;
        href: string;
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
    allowedSummaryAm: string[];
    deniedSummaryAm: string[];
};

type Props = {
    metrics: Metric[];
    queues: {
        title: string;
        emptyMessage?: string;
        items: { id: number; label: string; href: string }[];
    }[];
    recentActivity: ActivityItem[];
    scopeSummary?: ScopeSummary;
    demoOverview?: DemoOverview;
};

const props = defineProps<Props>();

const t = useT();
const page = usePage();
const auth = computed(() => page.props.auth as Auth);
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

const roleLabel = (roleKey: string): string => {
    const translated = t(`demo.roles.${roleKey}`);

    return translated.startsWith('demo.roles.') ? roleKey : translated;
};

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    {
        title: t('nav.dashboard'),
        href: dashboard(),
    },
]);

const switchScope = (scopeKey: string): void => {
    router.post(
        '/workspace/scope',
        { scope_key: scopeKey },
        { preserveScroll: true },
    );
};

const quickLinks = computed(() =>
    [
        {
            title: t('nav.users'),
            description: t('demo.dashboard.quick_users_desc'),
            href: usersIndex(),
            icon: Users,
            visible: auth.value.can.manageUsers,
        },
        {
            title: t('nav.roles'),
            description: t('demo.dashboard.quick_roles_desc'),
            href: rolesIndex(),
            icon: Shield,
            visible: auth.value.can.manageRoles,
        },
        {
            title: t('nav.media'),
            description: t('demo.dashboard.quick_media_desc'),
            href: mediaIndex(),
            icon: FolderOpen,
            visible: auth.value.permissions.includes('media.view'),
        },
        {
            title: t('nav.exports'),
            description: t('demo.dashboard.quick_exports_desc'),
            href: exportsIndex(),
            icon: FileOutput,
            visible: auth.value.can.viewExports,
        },
        {
            title: t('nav.system_settings'),
            description: t('demo.dashboard.quick_settings_desc'),
            href: adminSettingsEdit(),
            icon: Settings2,
            visible: auth.value.can.manageSettings,
        },
        {
            title: t('nav.activity_logs'),
            description: t('demo.dashboard.quick_activity_desc'),
            href: activityLogsIndex(),
            icon: ScrollText,
            visible: auth.value.can.viewActivityLogs,
        },
    ].filter((item) => item.visible),
);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="t('nav.dashboard')" />
        <PageContainer>
            <PageHeader
                :title="t('demo.dashboard.title')"
                :description="t('demo.dashboard.description')"
            />

            <!-- Active Actor & Scope Banner -->
            <section
                v-if="props.scopeSummary?.active"
                class="rounded-2xl border border-border/80 bg-card p-6 shadow-sm"
            >
                <div
                    class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between"
                >
                    <div class="space-y-2">
                        <div
                            class="inline-flex items-center gap-2 rounded-full bg-primary/10 px-3 py-1 text-xs font-semibold text-primary"
                        >
                            <span>{{
                                t('demo.dashboard.active_scope_badge')
                            }}</span>
                            <span>{{
                                roleLabel(props.scopeSummary.active.roleKey)
                            }}</span>
                        </div>
                        <h2 class="text-xl font-bold text-foreground">
                            {{ props.scopeSummary.active.title }}
                        </h2>
                        <p class="text-sm text-foreground">
                            <strong>{{
                                t('demo.dashboard.organization_label')
                            }}</strong>
                            {{ props.scopeSummary.active.organizationName }}
                        </p>
                        <p
                            v-if="
                                props.demoOverview?.organization
                                    ?.parishNameAm ||
                                props.demoOverview?.organization?.cityAm
                            "
                            class="text-xs text-muted-foreground"
                        >
                            <strong>{{
                                t('demo.dashboard.geography_label')
                            }}</strong>
                            {{
                                localizedText(
                                    props.demoOverview.organization
                                        .parishNameAm,
                                    props.demoOverview.organization
                                        .parishNameEn,
                                )
                            }}
                            ({{
                                localizedText(
                                    props.demoOverview.organization.cityAm,
                                    props.demoOverview.organization.cityEn,
                                )
                            }})
                        </p>
                        <p
                            v-if="props.scopeSummary.active.unitName"
                            class="text-sm text-foreground"
                        >
                            <strong>{{
                                t('demo.dashboard.internal_unit_label')
                            }}</strong>
                            {{ props.scopeSummary.active.unitName }}
                        </p>
                        <div
                            v-if="
                                props.scopeSummary.capabilities.permissionCodes
                                    ?.length
                            "
                            class="pt-1"
                        >
                            <div
                                class="text-xs font-semibold text-muted-foreground"
                            >
                                {{ t('demo.permissions.title') }}:
                            </div>
                            <div class="mt-1 flex flex-wrap gap-1.5">
                                <span
                                    v-for="code in props.scopeSummary
                                        .capabilities.permissionCodes"
                                    :key="code"
                                    class="rounded-md border border-border/80 bg-muted/40 px-2 py-0.5 font-mono text-[11px] text-foreground"
                                >
                                    {{ code }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Safe Scope Switcher when account holds multiple appointments -->
                    <div
                        v-if="props.scopeSummary.available.length > 1"
                        class="rounded-xl border border-amber-500/40 bg-amber-500/5 p-4 lg:max-w-md"
                    >
                        <div
                            class="text-xs font-semibold tracking-wide text-amber-700 uppercase dark:text-amber-300"
                        >
                            {{ t('demo.dashboard.scope_switcher_title') }}
                        </div>
                        <p class="mt-1 text-xs text-muted-foreground">
                            {{ t('demo.dashboard.scope_switcher_help') }}
                        </p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <Button
                                v-for="option in props.scopeSummary.available"
                                :key="option.key"
                                size="sm"
                                :variant="
                                    option.key === props.scopeSummary.active.key
                                        ? 'default'
                                        : 'outline'
                                "
                                @click="switchScope(option.key)"
                            >
                                {{ option.title }} ({{
                                    option.organizationName
                                }})
                            </Button>
                        </div>
                    </div>
                </div>

                <!-- Primary Action Buttons for Current Scope -->
                <div
                    class="mt-5 flex flex-wrap gap-3 border-t border-border/60 pt-4"
                >
                    <Button
                        v-if="props.demoOverview?.organization?.schoolHref"
                        as-child
                        variant="default"
                    >
                        <Link
                            :href="props.demoOverview.organization.schoolHref"
                        >
                            <Building2 class="mr-2 size-4" />
                            {{
                                t('demo.dashboard.open_school_workspace', {
                                    name: localizedText(
                                        props.demoOverview.organization.nameAm,
                                        props.demoOverview.organization.nameEn,
                                    ),
                                })
                            }}
                        </Link>
                    </Button>

                    <Button
                        v-if="props.demoOverview?.unit?.departmentHref"
                        as-child
                        variant="secondary"
                    >
                        <Link :href="props.demoOverview.unit.departmentHref">
                            <Users class="mr-2 size-4" />
                            {{
                                t('demo.dashboard.assigned_department', {
                                    name: localizedText(
                                        props.demoOverview.unit.nameAm,
                                        props.demoOverview.unit.nameEn,
                                    ),
                                })
                            }}
                        </Link>
                    </Button>

                    <Button
                        v-if="props.scopeSummary.capabilities.viewHierarchy"
                        as-child
                        variant="outline"
                    >
                        <Link href="/hierarchy">
                            <GitBranch class="mr-2 size-4" />
                            {{ t('nav.hierarchy') }}
                        </Link>
                    </Button>

                    <Button
                        v-if="props.scopeSummary.capabilities.viewReports"
                        as-child
                        variant="outline"
                    >
                        <Link href="/reports">
                            <FileSpreadsheet class="mr-2 size-4" />
                            {{ t('nav.reports') }} ({{
                                props.demoOverview?.visibleReportsCount ?? 0
                            }})
                        </Link>
                    </Button>

                    <Button as-child variant="outline">
                        <Link href="/my-profile">
                            <UserCheck class="mr-2 size-4" />
                            {{ t('nav.my_profile') }}
                        </Link>
                    </Button>
                </div>
            </section>

            <!-- National Summary for Union Super Admin / Board -->
            <section
                v-if="props.demoOverview?.nationalSummary"
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
                            {{
                                props.demoOverview.nationalSummary.diocesesCount
                            }}
                            /
                            {{
                                props.demoOverview.nationalSummary
                                    .districtsCount
                            }}
                        </div>
                    </div>
                    <div
                        class="rounded-xl border border-border/70 bg-background p-4"
                    >
                        <div class="text-xs text-muted-foreground">
                            {{ t('demo.national_summary.schools_count') }}
                        </div>
                        <div class="mt-1 text-2xl font-bold">
                            {{
                                props.demoOverview.nationalSummary.schoolsCount
                            }}
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
                            {{
                                props.demoOverview.nationalSummary
                                    .approvedMembersTotal
                            }}
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
                            {{
                                props.demoOverview.nationalSummary
                                    .releasedReportsTotal
                            }}
                            <span
                                class="text-xs font-normal text-muted-foreground"
                            >
                                ({{
                                    t(
                                        'demo.national_summary.reporting_schools',
                                    )
                                }}:
                                {{
                                    props.demoOverview.nationalSummary
                                        .reportingSchoolsCount
                                }}
                                ·
                                {{
                                    t('demo.national_summary.missing_schools')
                                }}:
                                {{
                                    props.demoOverview.nationalSummary
                                        .missingSchoolsCount
                                }})
                            </span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Allowed vs Denied Matrix for Current Persona -->
            <section
                v-if="props.demoOverview"
                class="grid gap-4 md:grid-cols-2"
            >
                <div
                    class="rounded-2xl border border-emerald-500/30 bg-emerald-500/5 p-5"
                >
                    <div
                        class="flex items-center gap-2 text-base font-semibold text-emerald-800 dark:text-emerald-300"
                    >
                        <CheckCircle2 class="size-5" />
                        <span>{{ t('demo.dashboard.allowed_title') }}</span>
                    </div>
                    <ul class="mt-3 space-y-2 text-sm text-foreground">
                        <li
                            v-for="(item, idx) in props.demoOverview
                                .allowedSummaryAm"
                            :key="idx"
                            class="flex items-start gap-2"
                        >
                            <span
                                class="mt-1 size-1.5 shrink-0 rounded-full bg-emerald-500"
                            />
                            <span>{{ item }}</span>
                        </li>
                    </ul>
                </div>

                <div
                    class="rounded-2xl border border-rose-500/30 bg-rose-500/5 p-5"
                >
                    <div
                        class="flex items-center gap-2 text-base font-semibold text-rose-800 dark:text-rose-300"
                    >
                        <Lock class="size-5" />
                        <span>{{ t('demo.dashboard.denied_title') }}</span>
                    </div>
                    <ul class="mt-3 space-y-2 text-sm text-foreground">
                        <li
                            v-for="(item, idx) in props.demoOverview
                                .deniedSummaryAm"
                            :key="idx"
                            class="flex items-start gap-2"
                        >
                            <span
                                class="mt-1 size-1.5 shrink-0 rounded-full bg-rose-500"
                            />
                            <span>{{ item }}</span>
                        </li>
                    </ul>
                </div>
            </section>

            <!-- Schools in Current Jurisdiction -->
            <section
                v-if="props.demoOverview?.jurisdictionSchools?.length"
                class="rounded-2xl border border-border/70 bg-card p-6 shadow-sm"
            >
                <h2 class="text-lg font-semibold">
                    {{ t('demo.dashboard.jurisdiction_schools_title') }}
                </h2>
                <div class="mt-4 grid gap-3 md:grid-cols-2 lg:grid-cols-3">
                    <Link
                        v-for="school in props.demoOverview.jurisdictionSchools"
                        :key="school.id"
                        :href="school.href"
                        class="rounded-xl border border-border/70 bg-background p-4 transition hover:border-primary/50"
                    >
                        <div class="font-semibold text-foreground">
                            {{ localizedText(school.nameAm, school.nameEn) }}
                        </div>
                        <div class="mt-2 text-xs text-muted-foreground">
                            {{ t('demo.dashboard.parish_prefix') }}
                            {{
                                localizedText(
                                    school.parishNameAm,
                                    school.parishNameEn,
                                )
                            }}
                            · {{ t('demo.dashboard.city_prefix') }}
                            {{ localizedText(school.cityAm, school.cityEn) }}
                        </div>
                    </Link>
                </div>
            </section>

            <!-- Technical Foundation Metrics (Visible to Technical Operator) -->
            <section
                v-if="metrics.length"
                class="grid gap-4 md:grid-cols-2 xl:grid-cols-4"
            >
                <Link
                    v-for="metric in metrics"
                    :key="metric.key"
                    :href="metric.href"
                    class="rounded-3xl transition hover:opacity-90 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-ring"
                >
                    <StatCard
                        :label="metric.label"
                        :value="metric.value"
                        :description="metric.description"
                        :tone="metric.tone"
                    />
                </Link>
            </section>

            <section
                v-if="recentActivity.length || quickLinks.length"
                class="grid gap-4 xl:grid-cols-[1.3fr_1fr]"
            >
                <RecentActivityPanel :items="recentActivity" />

                <section
                    class="rounded-[1.5rem] border border-border/70 bg-card/90 p-6 shadow-sm backdrop-blur"
                >
                    <h2 class="text-lg font-semibold">
                        {{ t('demo.dashboard.quick_access') }}
                    </h2>
                    <div class="mt-5 grid gap-3">
                        <Link
                            v-for="item in quickLinks"
                            :key="item.title"
                            :href="item.href"
                            class="rounded-2xl border border-border/70 bg-background/80 px-4 py-4 transition hover:border-foreground/20 hover:bg-background"
                        >
                            <div class="flex items-start gap-3">
                                <component
                                    :is="item.icon"
                                    class="mt-0.5 size-4 shrink-0 text-muted-foreground"
                                />
                                <div>
                                    <div
                                        class="text-sm font-medium text-foreground"
                                    >
                                        {{ item.title }}
                                    </div>
                                    <p
                                        class="mt-1 text-sm leading-6 text-muted-foreground"
                                    >
                                        {{ item.description }}
                                    </p>
                                </div>
                            </div>
                        </Link>
                    </div>
                </section>
            </section>
        </PageContainer>
    </AppLayout>
</template>
