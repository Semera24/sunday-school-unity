<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { Building2, GitBranch, MapPin, ShieldCheck } from 'lucide-vue-next';
import { computed, watch } from 'vue';
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

type UnitNode = {
    id: number;
    code: string;
    unitKind: string;
    categoryCode: string;
    nameAm: string;
    nameEn: string | null;
    canOpenDepartment: boolean;
};

type OrgNode = {
    id: number;
    parentId: number | null;
    code: string;
    type: string;
    status: string;
    nameAm: string;
    nameEn: string | null;
    cityAm: string | null;
    cityEn: string | null;
    parishNameAm: string | null;
    parishNameEn: string | null;
    inJurisdiction: boolean;
    canOpenSchoolWorkspace: boolean;
    canManageLocally: boolean;
    canRegisterSubordinate: boolean;
    canUpdateOrganization: boolean;
    canManageLocalLeads: boolean;
    approvedMembersCount: number | null;
    releasedReportsCount: number | null;
    units: UnitNode[];
};

type LeadAppointmentRow = {
    id: number;
    userId: number;
    userName: string;
    userEmail: string;
    organizationId: number;
    organizationNameAm: string;
    organizationNameEn: string | null;
    titleAm: string;
    titleEn: string | null;
    startsOn: string | null;
    endsOn: string | null;
    isActive: boolean;
    canUpdate: boolean;
};

type UserOption = {
    id: number;
    name: string;
    email: string;
};

const props = defineProps<{
    organizations: OrgNode[];
    localLeadAppointments: LeadAppointmentRow[];
    appointableUsers: UserOption[];
    scopeSummary: {
        active: {
            titleAm: string;
            organizationNameAm: string;
            roleKey: string;
        } | null;
    };
}>();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: t('nav.dashboard'), href: '/dashboard' },
    { title: t('nav.hierarchy'), href: '/hierarchy' },
]);

const registrableParents = computed(() =>
    props.organizations.filter((o) => o.canRegisterSubordinate),
);

const updatableOrganizations = computed(() =>
    props.organizations.filter((o) => o.canUpdateOrganization),
);

const manageableLeadSchools = computed(() =>
    props.organizations.filter((o) => o.canManageLocalLeads),
);

const form = useForm({
    parent_id: registrableParents.value[0]?.id ?? '',
    type: 'sunday_school',
    code: '',
    name_am: '',
    name_en: '',
    city_am: '',
    parish_name_am: '',
});

const submitNewOrganization = (): void => {
    form.post('/hierarchy/organizations', {
        preserveScroll: true,
        onSuccess: () =>
            form.reset(
                'code',
                'name_am',
                'name_en',
                'city_am',
                'parish_name_am',
            ),
    });
};

const initialEditOrg = updatableOrganizations.value[0];
const updateOrgForm = useForm({
    organization_id: initialEditOrg?.id ?? 0,
    name_am: initialEditOrg?.nameAm ?? '',
    name_en: initialEditOrg?.nameEn ?? '',
    city_am: initialEditOrg?.cityAm ?? '',
    city_en: initialEditOrg?.cityEn ?? '',
    parish_name_am: initialEditOrg?.parishNameAm ?? '',
    parish_name_en: initialEditOrg?.parishNameEn ?? '',
    status: initialEditOrg?.status ?? 'active',
});

watch(
    () => updateOrgForm.organization_id,
    (newId) => {
        const selected = updatableOrganizations.value.find(
            (o) => o.id === Number(newId),
        );

        if (selected) {
            updateOrgForm.name_am = selected.nameAm;
            updateOrgForm.name_en = selected.nameEn ?? '';
            updateOrgForm.city_am = selected.cityAm ?? '';
            updateOrgForm.city_en = selected.cityEn ?? '';
            updateOrgForm.parish_name_am = selected.parishNameAm ?? '';
            updateOrgForm.parish_name_en = selected.parishNameEn ?? '';
            updateOrgForm.status = selected.status ?? 'active';
        }
    },
);

const submitUpdateOrganization = (): void => {
    if (!updateOrgForm.organization_id) {
        return;
    }

    updateOrgForm.put(
        `/hierarchy/organizations/${updateOrgForm.organization_id}`,
        {
            preserveScroll: true,
        },
    );
};

const leadForm = useForm({
    organization_id: manageableLeadSchools.value[0]?.id ?? '',
    user_id: props.appointableUsers[0]?.id ?? '',
    title_am: 'የሰንበት ት/ቤት ዋና አስተባባሪ',
    title_en: 'Sunday School Lead',
    starts_on: '2026-01-01',
    ends_on: '',
    is_active: true,
});

const submitLocalLeadAppointment = (): void => {
    leadForm.post('/hierarchy/appointments', {
        preserveScroll: true,
    });
};

const toggleAppointmentActive = (appt: LeadAppointmentRow): void => {
    router.patch(
        `/appointments/${appt.id}`,
        { is_active: !appt.isActive },
        { preserveScroll: true },
    );
};

const typeLabel = (type: string): string => {
    const translated = t(`demo.types.${type}`);

    return translated.startsWith('demo.types.') ? type : translated;
};

const unitKindLabel = (kind: string): string => {
    const translated = t(`demo.unit_kinds.${kind}`);

    return translated.startsWith('demo.unit_kinds.') ? kind : translated;
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="t('nav.hierarchy')" />
        <PageContainer>
            <PageHeader
                :title="t('demo.hierarchy.title')"
                :description="t('demo.hierarchy.description')"
            />

            <div class="grid gap-4">
                <div
                    v-for="org in organizations"
                    :key="org.id"
                    class="rounded-2xl border p-5 shadow-sm transition"
                    :class="[
                        org.inJurisdiction
                            ? 'border-emerald-500/40 bg-card'
                            : 'border-border/60 bg-muted/30 opacity-85',
                        org.type === 'diocese' ? 'ml-4' : '',
                        org.type === 'district' ? 'ml-8' : '',
                        org.type === 'sunday_school' ? 'ml-12' : '',
                    ]"
                >
                    <div
                        class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between"
                    >
                        <div class="space-y-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span
                                    class="rounded-md bg-primary/10 px-2.5 py-0.5 text-xs font-semibold text-primary"
                                >
                                    {{ typeLabel(org.type) }}
                                </span>
                                <span
                                    class="font-mono text-xs text-muted-foreground"
                                >
                                    #{{ org.id }} · {{ org.code }}
                                </span>
                                <span
                                    v-if="org.inJurisdiction"
                                    class="inline-flex items-center gap-1 rounded-full bg-emerald-500/15 px-2.5 py-0.5 text-xs font-medium text-emerald-700 dark:text-emerald-300"
                                >
                                    <ShieldCheck class="size-3.5" />
                                    {{ t('demo.hierarchy.in_jurisdiction') }}
                                </span>
                                <span
                                    v-else
                                    class="rounded-full bg-muted px-2.5 py-0.5 text-xs text-muted-foreground"
                                >
                                    {{ t('demo.hierarchy.out_of_scope') }}
                                </span>
                            </div>

                            <h3 class="text-lg font-bold text-foreground">
                                {{ localizedText(org.nameAm, org.nameEn) }}
                            </h3>

                            <div
                                v-if="org.parishNameAm || org.cityAm"
                                class="flex flex-wrap items-center gap-3 text-xs text-muted-foreground"
                            >
                                <span
                                    v-if="org.parishNameAm"
                                    class="inline-flex items-center gap-1"
                                >
                                    <Building2 class="size-3.5" />
                                    {{ t('demo.hierarchy.parish_label') }}
                                    {{
                                        localizedText(
                                            org.parishNameAm,
                                            org.parishNameEn,
                                        )
                                    }}
                                </span>
                                <span
                                    v-if="org.cityAm"
                                    class="inline-flex items-center gap-1"
                                >
                                    <MapPin class="size-3.5" />
                                    {{ t('demo.hierarchy.city_label') }}
                                    {{ localizedText(org.cityAm, org.cityEn) }}
                                </span>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            <span
                                v-if="
                                    org.type === 'sunday_school' &&
                                    org.approvedMembersCount !== null
                                "
                                class="rounded-lg border px-2.5 py-1 text-xs"
                            >
                                {{
                                    t('demo.hierarchy.approved_members_count', {
                                        count: org.approvedMembersCount,
                                    })
                                }}
                            </span>
                            <span
                                v-if="
                                    org.type === 'sunday_school' &&
                                    org.releasedReportsCount !== null
                                "
                                class="rounded-lg border px-2.5 py-1 text-xs"
                            >
                                {{
                                    t('demo.hierarchy.released_reports_count', {
                                        count: org.releasedReportsCount,
                                    })
                                }}
                            </span>

                            <Button
                                v-if="org.canOpenSchoolWorkspace"
                                size="sm"
                                :variant="
                                    org.canManageLocally ? 'default' : 'outline'
                                "
                                as-child
                            >
                                <Link :href="`/schools/${org.id}`">
                                    {{
                                        org.canManageLocally
                                            ? t(
                                                  'demo.hierarchy.manage_school_button',
                                              )
                                            : t(
                                                  'demo.hierarchy.view_school_button',
                                              )
                                    }}
                                </Link>
                            </Button>
                        </div>
                    </div>

                    <!-- Internal Units (Board, Divisions, Local Departments) -->
                    <div
                        v-if="org.units.length > 0"
                        class="mt-4 border-t border-border/60 pt-3"
                    >
                        <div
                            class="text-xs font-semibold text-muted-foreground"
                        >
                            {{ t('demo.hierarchy.internal_units_label') }}
                        </div>
                        <div class="mt-2 flex flex-wrap gap-2">
                            <template v-for="unit in org.units" :key="unit.id">
                                <Link
                                    v-if="unit.canOpenDepartment"
                                    :href="`/departments/${unit.id}`"
                                    class="inline-flex items-center gap-1.5 rounded-lg border border-primary/40 bg-primary/5 px-3 py-1 text-xs font-medium text-primary hover:bg-primary/10"
                                >
                                    <GitBranch class="size-3" />
                                    <span>{{
                                        localizedText(unit.nameAm, unit.nameEn)
                                    }}</span>
                                    <span class="text-[10px] opacity-75"
                                        >({{ unitKindLabel(unit.unitKind) }} ·
                                        {{ t('common.open') }})</span
                                    >
                                </Link>
                                <span
                                    v-else
                                    class="inline-flex items-center gap-1.5 rounded-lg border border-border/70 bg-background px-3 py-1 text-xs text-muted-foreground"
                                >
                                    <span>{{
                                        localizedText(unit.nameAm, unit.nameEn)
                                    }}</span>
                                    <span class="text-[10px] opacity-75"
                                        >({{
                                            unitKindLabel(unit.unitKind)
                                        }})</span
                                    >
                                </span>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Scoped Subordinate Organization Registration -->
            <section
                v-if="registrableParents.length > 0"
                class="mt-6 rounded-2xl border border-border/80 bg-card p-6 shadow-sm"
            >
                <h2 class="text-lg font-semibold">
                    {{ t('demo.hierarchy.register_title') }}
                </h2>
                <p class="mt-1 text-sm text-muted-foreground">
                    {{ t('demo.hierarchy.register_description') }}
                </p>

                <form
                    class="mt-4 grid gap-4 md:grid-cols-3"
                    @submit.prevent="submitNewOrganization"
                >
                    <div class="grid gap-1.5">
                        <Label for="parent_id">{{
                            t('demo.hierarchy.parent_org')
                        }}</Label>
                        <select
                            id="parent_id"
                            v-model="form.parent_id"
                            class="h-10 rounded-md border border-input bg-background px-3 text-sm"
                        >
                            <option
                                v-for="p in registrableParents"
                                :key="p.id"
                                :value="p.id"
                            >
                                {{ localizedText(p.nameAm, p.nameEn) }} ({{
                                    typeLabel(p.type)
                                }})
                            </option>
                        </select>
                        <InputError :message="form.errors.parent_id" />
                    </div>

                    <div class="grid gap-1.5">
                        <Label for="type">{{
                            t('demo.hierarchy.org_type')
                        }}</Label>
                        <select
                            id="type"
                            v-model="form.type"
                            class="h-10 rounded-md border border-input bg-background px-3 text-sm"
                        >
                            <option
                                v-if="
                                    props.scopeSummary.active?.roleKey ===
                                    'union_super_admin'
                                "
                                value="diocese"
                            >
                                {{ t('demo.types.diocese') }}
                            </option>
                            <option value="sunday_school">
                                {{ t('demo.types.sunday_school') }}
                            </option>
                            <option value="district">
                                {{ t('demo.types.district') }}
                            </option>
                        </select>
                        <InputError :message="form.errors.type" />
                    </div>

                    <div class="grid gap-1.5">
                        <Label for="code">{{
                            t('demo.hierarchy.unique_code')
                        }}</Label>
                        <Input
                            id="code"
                            v-model="form.code"
                            placeholder="school-new-demo"
                            required
                        />
                        <InputError :message="form.errors.code" />
                    </div>

                    <div class="grid gap-1.5">
                        <Label for="name_am">{{
                            t('demo.hierarchy.name_am')
                        }}</Label>
                        <Input
                            id="name_am"
                            v-model="form.name_am"
                            placeholder="አዲስ ሰንበት ት/ቤት"
                            required
                        />
                        <InputError :message="form.errors.name_am" />
                    </div>

                    <div class="grid gap-1.5">
                        <Label for="parish_name_am">{{
                            t('demo.hierarchy.parish_name_am')
                        }}</Label>
                        <Input
                            id="parish_name_am"
                            v-model="form.parish_name_am"
                            placeholder="ቅዱስ ገብርኤል ቤተ ክርስቲያን"
                        />
                    </div>

                    <div class="grid gap-1.5">
                        <Label for="city_am">{{
                            t('demo.hierarchy.city_am')
                        }}</Label>
                        <Input
                            id="city_am"
                            v-model="form.city_am"
                            placeholder="አዲስ አበባ"
                        />
                    </div>

                    <div class="md:col-span-3">
                        <Button type="submit" :disabled="form.processing">
                            {{ t('demo.hierarchy.register_submit') }}
                        </Button>
                    </div>
                </form>
            </section>

            <!-- Nationwide Organization Update Form (Union Super Admin Only) -->
            <section
                v-if="updatableOrganizations.length > 0"
                class="mt-6 rounded-2xl border border-border/80 bg-card p-6 shadow-sm"
            >
                <h2 class="text-lg font-semibold">
                    {{ t('demo.org_update.title') }}
                </h2>
                <p class="mt-1 text-sm text-muted-foreground">
                    {{ t('demo.org_update.description') }}
                </p>

                <form
                    class="mt-4 grid gap-4 md:grid-cols-3"
                    @submit.prevent="submitUpdateOrganization"
                >
                    <div class="grid gap-1.5">
                        <Label for="edit_org_id">{{
                            t('demo.org_update.select_org')
                        }}</Label>
                        <select
                            id="edit_org_id"
                            v-model="updateOrgForm.organization_id"
                            class="h-10 rounded-md border border-input bg-background px-3 text-sm"
                        >
                            <option
                                v-for="org in updatableOrganizations"
                                :key="org.id"
                                :value="org.id"
                            >
                                #{{ org.id }} ·
                                {{ localizedText(org.nameAm, org.nameEn) }} ({{
                                    org.code
                                }})
                            </option>
                        </select>
                    </div>

                    <div class="grid gap-1.5">
                        <Label for="edit_name_am">{{
                            t('demo.org_update.name_am')
                        }}</Label>
                        <Input
                            id="edit_name_am"
                            v-model="updateOrgForm.name_am"
                            required
                        />
                        <InputError :message="updateOrgForm.errors.name_am" />
                    </div>

                    <div class="grid gap-1.5">
                        <Label for="edit_name_en">{{
                            t('demo.org_update.name_en')
                        }}</Label>
                        <Input
                            id="edit_name_en"
                            v-model="updateOrgForm.name_en"
                        />
                        <InputError :message="updateOrgForm.errors.name_en" />
                    </div>

                    <div class="grid gap-1.5">
                        <Label for="edit_city_am">{{
                            t('demo.org_update.city_am')
                        }}</Label>
                        <Input
                            id="edit_city_am"
                            v-model="updateOrgForm.city_am"
                        />
                    </div>

                    <div class="grid gap-1.5">
                        <Label for="edit_parish_am">{{
                            t('demo.org_update.parish_name_am')
                        }}</Label>
                        <Input
                            id="edit_parish_am"
                            v-model="updateOrgForm.parish_name_am"
                        />
                    </div>

                    <div class="grid gap-1.5">
                        <Label for="edit_status">{{
                            t('demo.org_update.status')
                        }}</Label>
                        <select
                            id="edit_status"
                            v-model="updateOrgForm.status"
                            class="h-10 rounded-md border border-input bg-background px-3 text-sm"
                        >
                            <option value="active">
                                {{ t('demo.org_update.status_active') }}
                            </option>
                            <option value="inactive">
                                {{ t('demo.org_update.status_inactive') }}
                            </option>
                        </select>
                        <InputError :message="updateOrgForm.errors.status" />
                    </div>

                    <div class="md:col-span-3">
                        <Button
                            type="submit"
                            :disabled="updateOrgForm.processing"
                        >
                            {{ t('demo.org_update.submit') }}
                        </Button>
                    </div>
                </form>
            </section>

            <!-- Local Sunday School Lead Appointments (Union Super Admin Only) -->
            <section
                v-if="manageableLeadSchools.length > 0"
                class="mt-6 rounded-2xl border border-border/80 bg-card p-6 shadow-sm"
            >
                <h2 class="text-lg font-semibold">
                    {{ t('demo.lead_appointments.title') }}
                </h2>
                <p class="mt-1 text-sm text-muted-foreground">
                    {{ t('demo.lead_appointments.description') }}
                </p>

                <form
                    class="mt-4 grid gap-4 md:grid-cols-3"
                    @submit.prevent="submitLocalLeadAppointment"
                >
                    <div class="grid gap-1.5">
                        <Label for="lead_school_id">{{
                            t('demo.lead_appointments.school')
                        }}</Label>
                        <select
                            id="lead_school_id"
                            v-model="leadForm.organization_id"
                            class="h-10 rounded-md border border-input bg-background px-3 text-sm"
                        >
                            <option
                                v-for="s in manageableLeadSchools"
                                :key="s.id"
                                :value="s.id"
                            >
                                {{ localizedText(s.nameAm, s.nameEn) }} ({{
                                    s.code
                                }})
                            </option>
                        </select>
                        <InputError
                            :message="leadForm.errors.organization_id"
                        />
                    </div>

                    <div class="grid gap-1.5">
                        <Label for="lead_user_id">{{
                            t('demo.lead_appointments.user')
                        }}</Label>
                        <select
                            id="lead_user_id"
                            v-model="leadForm.user_id"
                            class="h-10 rounded-md border border-input bg-background px-3 text-sm"
                        >
                            <option
                                v-for="u in appointableUsers"
                                :key="u.id"
                                :value="u.id"
                            >
                                {{ u.name }} ({{ u.email }})
                            </option>
                        </select>
                        <InputError :message="leadForm.errors.user_id" />
                    </div>

                    <div class="grid gap-1.5">
                        <Label for="lead_title_am">{{
                            t('demo.lead_appointments.title_am')
                        }}</Label>
                        <Input
                            id="lead_title_am"
                            v-model="leadForm.title_am"
                            required
                        />
                        <InputError :message="leadForm.errors.title_am" />
                    </div>

                    <div class="grid gap-1.5">
                        <Label for="lead_title_en">{{
                            t('demo.lead_appointments.title_en')
                        }}</Label>
                        <Input id="lead_title_en" v-model="leadForm.title_en" />
                    </div>

                    <div class="grid gap-1.5">
                        <Label for="lead_starts_on">{{
                            t('demo.lead_appointments.starts_on')
                        }}</Label>
                        <Input
                            id="lead_starts_on"
                            v-model="leadForm.starts_on"
                            type="date"
                            required
                        />
                        <InputError :message="leadForm.errors.starts_on" />
                    </div>

                    <div class="grid gap-1.5">
                        <Label for="lead_ends_on">{{
                            t('demo.lead_appointments.ends_on')
                        }}</Label>
                        <Input
                            id="lead_ends_on"
                            v-model="leadForm.ends_on"
                            type="date"
                        />
                        <InputError :message="leadForm.errors.ends_on" />
                    </div>

                    <div class="md:col-span-3">
                        <Button type="submit" :disabled="leadForm.processing">
                            {{ t('demo.lead_appointments.assign_button') }}
                        </Button>
                    </div>
                </form>

                <!-- Existing Sunday School Lead Appointments List -->
                <div
                    v-if="localLeadAppointments.length > 0"
                    class="mt-6 border-t border-border/60 pt-5"
                >
                    <h3 class="text-sm font-semibold">
                        {{ t('demo.lead_appointments.current_list') }}
                    </h3>
                    <div class="mt-3 space-y-2">
                        <div
                            v-for="appt in localLeadAppointments"
                            :key="appt.id"
                            class="flex flex-col justify-between gap-3 rounded-xl border border-border/70 bg-background p-3.5 text-sm md:flex-row md:items-center"
                        >
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="font-semibold">{{
                                        localizedText(
                                            appt.organizationNameAm,
                                            appt.organizationNameEn,
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
                                    @click="toggleAppointmentActive(appt)"
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
        </PageContainer>
    </AppLayout>
</template>
