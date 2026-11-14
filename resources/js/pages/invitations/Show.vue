<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useT } from '@/composables/useT';
import AuthBase from '@/layouts/AuthLayout.vue';

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

type Props = {
    state: 'ready' | 'invalid' | 'revoked' | 'expired' | 'already_accepted';
    invitation: {
        token?: string;
        invitedEmail?: string;
        invitedName?: string;
        schoolNameAm?: string | null;
        schoolNameEn?: string | null;
        expiresAt?: string | null;
        status?: string;
        requiresExistingLogin?: boolean;
        signedInMatchesInvitedEmail?: boolean;
        wrongSignedInAccount?: boolean;
        signedInEmail?: string | null;
    } | null;
};

const props = defineProps<Props>();

const form = useForm({
    name: props.invitation?.invitedName ?? '',
    locale: (page.props.locale as string) || 'am',
    password: '',
    password_confirmation: '',
});

const acceptInvitation = (): void => {
    if (!props.invitation?.token) {
        return;
    }

    form.post(`/invitations/${props.invitation.token}/accept`, {
        preserveScroll: true,
    });
};
</script>

<template>
    <AuthBase
        :title="t('demo.invitations.page_title')"
        :description="t('demo.invitations.page_description')"
    >
        <Head :title="t('demo.invitations.page_title')" />

        <!-- Invalid State -->
        <div
            v-if="state === 'invalid'"
            class="rounded-xl border border-destructive/30 bg-destructive/10 p-4 text-sm"
        >
            <h2 class="font-semibold text-destructive">
                {{ t('demo.invitations.state_invalid_title') }}
            </h2>
            <p class="mt-1 text-xs text-muted-foreground">
                {{ t('demo.invitations.state_invalid_body') }}
            </p>
            <div class="mt-4">
                <Button size="sm" variant="outline" as-child>
                    <Link href="/login">{{ t('auth.login') }}</Link>
                </Button>
            </div>
        </div>

        <!-- Revoked State -->
        <div
            v-else-if="state === 'revoked'"
            class="rounded-xl border border-amber-500/30 bg-amber-500/10 p-4 text-sm"
        >
            <h2 class="font-semibold text-amber-800 dark:text-amber-300">
                {{ t('demo.invitations.state_revoked_title') }}
            </h2>
            <p class="mt-1 text-xs text-muted-foreground">
                {{ t('demo.invitations.state_revoked_body') }}
            </p>
        </div>

        <!-- Expired State -->
        <div
            v-else-if="state === 'expired'"
            class="rounded-xl border border-amber-500/30 bg-amber-500/10 p-4 text-sm"
        >
            <h2 class="font-semibold text-amber-800 dark:text-amber-300">
                {{ t('demo.invitations.state_expired_title') }}
            </h2>
            <p class="mt-1 text-xs text-muted-foreground">
                {{
                    t('demo.invitations.state_expired_body', {
                        date: invitation?.expiresAt ?? '—',
                    })
                }}
            </p>
        </div>

        <!-- Already Accepted State -->
        <div
            v-else-if="state === 'already_accepted'"
            class="rounded-xl border border-border/80 bg-card p-4 text-sm"
        >
            <h2 class="font-semibold">
                {{ t('demo.invitations.state_already_accepted_title') }}
            </h2>
            <p class="mt-1 text-xs text-muted-foreground">
                {{ t('demo.invitations.state_already_accepted_body') }}
            </p>
            <div class="mt-4">
                <Button size="sm" as-child>
                    <Link href="/my-profile">{{ t('nav.my_profile') }}</Link>
                </Button>
            </div>
        </div>

        <!-- Ready State -->
        <div v-else-if="state === 'ready' && invitation" class="space-y-5">
            <div
                class="rounded-xl border border-border/80 bg-muted/30 p-4 text-sm"
            >
                <div class="text-xs text-muted-foreground">
                    {{ t('demo.invitations.invited_school_label') }}
                </div>
                <div class="font-semibold">
                    {{
                        localizedText(
                            invitation.schoolNameAm,
                            invitation.schoolNameEn,
                        )
                    }}
                </div>
                <div class="mt-2 text-xs text-muted-foreground">
                    {{ t('demo.invitations.invited_member_label') }}
                </div>
                <div class="font-medium">
                    {{ invitation.invitedName || '—' }}
                </div>
                <div class="mt-2 text-xs text-muted-foreground">
                    {{ t('demo.invitations.invited_email_label') }}
                </div>
                <div class="font-mono text-xs">
                    {{ invitation.invitedEmail }}
                </div>
            </div>

            <!-- Wrong Signed-In Account Guard -->
            <div
                v-if="invitation.wrongSignedInAccount"
                class="rounded-xl border border-amber-500/40 bg-amber-500/10 p-4 text-sm"
            >
                <h2 class="font-semibold text-amber-800 dark:text-amber-300">
                    {{ t('demo.invitations.wrong_account_heading') }}
                </h2>
                <p class="mt-1 text-xs text-muted-foreground">
                    {{
                        t('demo.invitations.wrong_account_body', {
                            current_email: invitation.signedInEmail ?? '',
                            invited_email: invitation.invitedEmail ?? '',
                        })
                    }}
                </p>
                <div class="mt-3">
                    <Button size="sm" variant="outline" as-child>
                        <Link href="/logout" method="post" as="button">
                            {{ t('demo.invitations.sign_out_first') }}
                        </Link>
                    </Button>
                </div>
            </div>

            <!-- Existing Account Path -->
            <template v-else-if="invitation.requiresExistingLogin">
                <div
                    v-if="invitation.signedInMatchesInvitedEmail"
                    class="space-y-4"
                >
                    <h2 class="text-sm font-semibold">
                        {{ t('demo.invitations.existing_account_heading') }}
                    </h2>
                    <p class="text-xs text-muted-foreground">
                        {{
                            t(
                                'demo.invitations.existing_account_SignedIn_match',
                                {
                                    email: invitation.invitedEmail ?? '',
                                },
                            )
                        }}
                    </p>
                    <form @submit.prevent="acceptInvitation">
                        <InputError
                            :message="
                                (form.errors as Record<string, string>)
                                    .invitation ||
                                (form.errors as Record<string, string>).email
                            "
                        />
                        <Button
                            type="submit"
                            class="w-full"
                            :disabled="form.processing"
                        >
                            {{ t('demo.invitations.accept_button') }}
                        </Button>
                    </form>
                </div>

                <div
                    v-else
                    class="rounded-xl border border-amber-500/30 bg-amber-500/10 p-4 text-sm"
                >
                    <h2
                        class="font-semibold text-amber-800 dark:text-amber-300"
                    >
                        {{ t('demo.invitations.existing_account_heading') }}
                    </h2>
                    <p class="mt-1 text-xs text-muted-foreground">
                        {{
                            t('demo.invitations.existing_account_must_login', {
                                email: invitation.invitedEmail ?? '',
                            })
                        }}
                    </p>
                    <div class="mt-3">
                        <Button size="sm" as-child>
                            <Link href="/login">
                                {{ t('demo.invitations.go_to_login') }}
                            </Link>
                        </Button>
                    </div>
                </div>
            </template>

            <!-- New Account Creation Path -->
            <form v-else class="space-y-4" @submit.prevent="acceptInvitation">
                <h2 class="text-sm font-semibold">
                    {{ t('demo.invitations.create_account_heading') }}
                </h2>

                <InputError
                    :message="
                        (form.errors as Record<string, string>).invitation
                    "
                />

                <div class="grid gap-1.5">
                    <Label for="inv_name">{{ t('profile.full_name') }}</Label>
                    <Input id="inv_name" v-model="form.name" required />
                    <InputError :message="form.errors.name" />
                </div>

                <div class="grid gap-1.5">
                    <Label for="inv_locale">{{ t('profile.language') }}</Label>
                    <select
                        id="inv_locale"
                        v-model="form.locale"
                        class="h-10 rounded-md border border-input bg-background px-3 text-sm"
                    >
                        <option value="am">አማርኛ (Amharic)</option>
                        <option value="en">English</option>
                    </select>
                </div>

                <div class="grid gap-1.5">
                    <Label for="inv_password">{{ t('auth.password') }}</Label>
                    <Input
                        id="inv_password"
                        v-model="form.password"
                        type="password"
                        required
                        autocomplete="new-password"
                    />
                    <InputError :message="form.errors.password" />
                </div>

                <div class="grid gap-1.5">
                    <Label for="inv_password_confirmation">{{
                        t('settings.security.confirm_password')
                    }}</Label>
                    <Input
                        id="inv_password_confirmation"
                        v-model="form.password_confirmation"
                        type="password"
                        required
                        autocomplete="new-password"
                    />
                </div>

                <Button
                    type="submit"
                    class="w-full"
                    :disabled="form.processing"
                >
                    {{ t('demo.invitations.accept_button') }}
                </Button>
            </form>
        </div>
    </AuthBase>
</template>
