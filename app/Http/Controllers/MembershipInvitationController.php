<?php

namespace App\Http\Controllers;

use App\Concerns\PasswordValidationRules;
use App\Models\MembershipDecision;
use App\Models\MembershipInvitation;
use App\Models\SchoolMembership;
use App\Models\User;
use App\Support\ActivityLogger;
use App\Support\HierarchyScopeContext;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class MembershipInvitationController extends Controller
{
    use PasswordValidationRules;

    public function show(Request $request, string $token): Response
    {
        $tokenHash = hash('sha256', $token);

        $invitation = MembershipInvitation::query()
            ->with(['organization', 'schoolMembership.person'])
            ->where('token_hash', $tokenHash)
            ->first();

        if ($invitation === null) {
            return Inertia::render('invitations/Show', [
                'state' => 'invalid',
                'invitation' => null,
            ]);
        }

        if ($invitation->status === MembershipInvitation::STATUS_REVOKED) {
            return Inertia::render('invitations/Show', [
                'state' => 'revoked',
                'invitation' => [
                    'schoolNameAm' => $invitation->organization?->name_am,
                    'schoolNameEn' => $invitation->organization?->name_en,
                ],
            ]);
        }

        if ($invitation->isExpired()) {
            if ($invitation->status === MembershipInvitation::STATUS_PENDING_ACCEPTANCE) {
                $invitation->update([
                    'status' => MembershipInvitation::STATUS_EXPIRED,
                ]);
            }

            return Inertia::render('invitations/Show', [
                'state' => 'expired',
                'invitation' => [
                    'schoolNameAm' => $invitation->organization?->name_am,
                    'schoolNameEn' => $invitation->organization?->name_en,
                    'expiresAt' => $invitation->expires_at?->toDateTimeString(),
                ],
            ]);
        }

        if ($invitation->status !== MembershipInvitation::STATUS_PENDING_ACCEPTANCE) {
            return Inertia::render('invitations/Show', [
                'state' => 'already_accepted',
                'invitation' => [
                    'schoolNameAm' => $invitation->organization?->name_am,
                    'schoolNameEn' => $invitation->organization?->name_en,
                    'status' => $invitation->status,
                ],
            ]);
        }

        $membership = $invitation->schoolMembership;
        if (
            $membership === null
            || ! in_array($membership->status, [SchoolMembership::STATUS_APPROVED, SchoolMembership::STATUS_PENDING], true)
            || $membership->user_id !== null
            || $membership->pending_user_id !== null
            || $membership->account_link_status === SchoolMembership::LINK_PENDING_LEAD_APPROVAL
        ) {
            return Inertia::render('invitations/Show', [
                'state' => 'revoked',
                'invitation' => [
                    'schoolNameAm' => $invitation->organization?->name_am,
                    'schoolNameEn' => $invitation->organization?->name_en,
                ],
            ]);
        }

        $invitedEmailLower = mb_strtolower(trim($invitation->invited_email));
        $existingUserExists = User::query()
            ->whereRaw('LOWER(email) = ?', [$invitedEmailLower])
            ->exists();

        $signedInUser = $request->user();
        $signedInMatchesInvitedEmail = $signedInUser !== null
            && mb_strtolower(trim($signedInUser->email)) === $invitedEmailLower;
        $wrongSignedInAccount = $signedInUser !== null && ! $signedInMatchesInvitedEmail;

        return Inertia::render('invitations/Show', [
            'state' => 'ready',
            'invitation' => [
                'token' => $token,
                'invitedEmail' => $invitation->invited_email,
                'invitedName' => $membership->person?->full_name ?? $invitation->invited_name ?? '',
                'schoolNameAm' => $invitation->organization?->name_am ?? '',
                'schoolNameEn' => $invitation->organization?->name_en,
                'expiresAt' => $invitation->expires_at?->toDateTimeString(),
                'requiresExistingLogin' => $existingUserExists,
                'signedInMatchesInvitedEmail' => $signedInMatchesInvitedEmail,
                'wrongSignedInAccount' => $wrongSignedInAccount,
                'signedInEmail' => $signedInUser?->email,
            ],
        ]);
    }

    public function accept(Request $request, string $token): RedirectResponse
    {
        $tokenHash = hash('sha256', $token);

        $initialInvitation = MembershipInvitation::query()
            ->where('token_hash', $tokenHash)
            ->first();

        if ($initialInvitation === null) {
            throw ValidationException::withMessages([
                'invitation' => [__('messages.demo.invitations.error_invalid')],
            ]);
        }

        if ($initialInvitation->isExpired()) {
            if ($initialInvitation->status === MembershipInvitation::STATUS_PENDING_ACCEPTANCE) {
                $initialInvitation->update([
                    'status' => MembershipInvitation::STATUS_EXPIRED,
                ]);
            }

            throw ValidationException::withMessages([
                'invitation' => [__('messages.demo.invitations.error_expired')],
            ]);
        }

        try {
            /** @var array{user: User, membership: SchoolMembership, requiresLogin: bool, auditTechnicalDeny: User|null} $result */
            $result = DB::transaction(function () use ($request, $initialInvitation): array {
                // Lock order: 1. SchoolMembership -> 2. MembershipInvitation -> 3. User
                $membership = SchoolMembership::query()
                    ->whereKey($initialInvitation->school_membership_id)
                    ->where('organization_id', $initialInvitation->organization_id)
                    ->lockForUpdate()
                    ->first();

                if ($membership === null) {
                    throw ValidationException::withMessages([
                        'invitation' => [__('messages.demo.invitations.error_invalid')],
                    ]);
                }

                $invitation = MembershipInvitation::query()
                    ->whereKey($initialInvitation->id)
                    ->where('school_membership_id', $membership->id)
                    ->where('organization_id', $membership->organization_id)
                    ->lockForUpdate()
                    ->first();

                if ($invitation === null) {
                    throw ValidationException::withMessages([
                        'invitation' => [__('messages.demo.invitations.error_invalid')],
                    ]);
                }

                // Idempotent double-submit check when the same user already submitted this exact invitation
                $signedInUser = $request->user();
                if (
                    $invitation->status === MembershipInvitation::STATUS_PENDING_LEAD_APPROVAL
                    && $invitation->accepted_by_user_id !== null
                    && $signedInUser !== null
                    && (int) $signedInUser->id === (int) $invitation->accepted_by_user_id
                    && (int) $membership->pending_user_id === (int) $invitation->accepted_by_user_id
                ) {
                    return [
                        'user' => $signedInUser,
                        'membership' => $membership,
                        'requiresLogin' => false,
                        'auditTechnicalDeny' => null,
                    ];
                }

                if ($invitation->status === MembershipInvitation::STATUS_REVOKED) {
                    throw ValidationException::withMessages([
                        'invitation' => [__('messages.demo.invitations.error_revoked')],
                    ]);
                }

                if ($invitation->isExpired()) {
                    throw ValidationException::withMessages([
                        'invitation' => [__('messages.demo.invitations.error_expired')],
                    ]);
                }

                if ($invitation->status !== MembershipInvitation::STATUS_PENDING_ACCEPTANCE) {
                    throw ValidationException::withMessages([
                        'invitation' => [__('messages.demo.invitations.error_already_used')],
                    ]);
                }

                if (! in_array($membership->status, [SchoolMembership::STATUS_APPROVED, SchoolMembership::STATUS_PENDING], true)) {
                    throw ValidationException::withMessages([
                        'invitation' => [__('messages.demo.invitations.error_membership_not_eligible')],
                    ]);
                }

                if ($membership->user_id !== null || $membership->account_link_status === SchoolMembership::LINK_APPROVED) {
                    throw ValidationException::withMessages([
                        'invitation' => [__('messages.demo.invitations.error_already_linked')],
                    ]);
                }

                if ($membership->pending_user_id !== null || $membership->account_link_status === SchoolMembership::LINK_PENDING_LEAD_APPROVAL) {
                    throw ValidationException::withMessages([
                        'invitation' => [__('messages.demo.invitations.error_pending_lead_review_exists')],
                    ]);
                }

                $otherPendingLeadInvitation = MembershipInvitation::query()
                    ->where('school_membership_id', $membership->id)
                    ->where('id', '!=', $invitation->id)
                    ->where('status', MembershipInvitation::STATUS_PENDING_LEAD_APPROVAL)
                    ->lockForUpdate()
                    ->exists();

                if ($otherPendingLeadInvitation) {
                    throw ValidationException::withMessages([
                        'invitation' => [__('messages.demo.invitations.error_pending_lead_review_exists')],
                    ]);
                }

                $invitedEmailLower = mb_strtolower(trim($invitation->invited_email));

                // Never allow a currently signed-in user with a different email to accept another person's invitation
                if ($signedInUser !== null && mb_strtolower(trim($signedInUser->email)) !== $invitedEmailLower) {
                    throw ValidationException::withMessages([
                        'invitation' => [__('messages.demo.invitations.error_wrong_signed_in_account', [
                            'current_email' => $signedInUser->email,
                            'invited_email' => $invitation->invited_email,
                        ])],
                    ]);
                }

                $existingUser = User::query()
                    ->whereRaw('LOWER(email) = ?', [$invitedEmailLower])
                    ->lockForUpdate()
                    ->first();

                $requiresLogin = false;

                if ($existingUser !== null) {
                    if ($signedInUser === null || (int) $signedInUser->id !== (int) $existingUser->id) {
                        throw ValidationException::withMessages([
                            'email' => [__('messages.demo.invitations.must_login_existing', ['email' => $invitation->invited_email])],
                        ]);
                    }

                    if ($existingUser->hasTechnicalIdentity()) {
                        return [
                            'user' => $existingUser,
                            'membership' => $membership,
                            'requiresLogin' => false,
                            'auditTechnicalDeny' => $existingUser,
                        ];
                    }

                    // Prevent one login account from becoming a second person in the same Sunday School
                    $hasSameSchoolMembership = SchoolMembership::query()
                        ->where('organization_id', $membership->organization_id)
                        ->where('id', '!=', $membership->id)
                        ->where(function ($q) use ($existingUser): void {
                            $q->where('user_id', $existingUser->id)
                                ->orWhere('pending_user_id', $existingUser->id);
                        })
                        ->lockForUpdate()
                        ->exists();

                    if ($hasSameSchoolMembership) {
                        throw ValidationException::withMessages([
                            'invitation' => [__('messages.demo.invitations.error_user_already_member_same_school')],
                        ]);
                    }

                    // Cross-school account claiming / identity reconciliation is deferred in this milestone
                    $hasOtherSchoolMembership = SchoolMembership::query()
                        ->where('organization_id', '!=', $membership->organization_id)
                        ->where(function ($q) use ($existingUser): void {
                            $q->where('user_id', $existingUser->id)
                                ->orWhere('pending_user_id', $existingUser->id);
                        })
                        ->lockForUpdate()
                        ->exists();

                    if ($hasOtherSchoolMembership) {
                        throw ValidationException::withMessages([
                            'invitation' => [__('messages.demo.invitations.error_cross_school_claim_deferred')],
                        ]);
                    }

                    if (! $existingUser->is_business_account) {
                        $existingUser->update([
                            'is_business_account' => true,
                        ]);
                    }

                    $acceptedUser = $existingUser;
                } else {
                    $validated = $request->validate([
                        'name' => ['required', 'string', 'max:255'],
                        'locale' => ['nullable', 'string', Rule::in(['am', 'en'])],
                        'password' => $this->passwordRules(),
                    ]);

                    $acceptedUser = User::query()->create([
                        'name' => $validated['name'],
                        'email' => $invitedEmailLower,
                        'locale' => $validated['locale'] ?? app()->getLocale(),
                        'is_business_account' => true,
                        'password' => $validated['password'],
                        'email_verified_at' => now(),
                    ]);

                    $requiresLogin = true;
                }

                $invitation->update([
                    'status' => MembershipInvitation::STATUS_PENDING_LEAD_APPROVAL,
                    'accepted_at' => now(),
                    'accepted_by_user_id' => $acceptedUser->id,
                ]);

                $membership->update([
                    'pending_user_id' => $acceptedUser->id,
                    'account_link_status' => SchoolMembership::LINK_PENDING_LEAD_APPROVAL,
                ]);

                MembershipDecision::query()->create([
                    'school_membership_id' => $membership->id,
                    'organization_id' => $membership->organization_id,
                    'actor_user_id' => $acceptedUser->id,
                    'target_user_id' => $acceptedUser->id,
                    'action' => MembershipDecision::ACTION_INVITATION_ACCEPTED_PENDING_LEAD,
                    'from_status' => $membership->status,
                    'to_status' => $membership->status,
                    'reason' => __('messages.demo.decisions.default_invitation_accepted_note'),
                ]);

                ActivityLogger::record(
                    actor: $acceptedUser,
                    event: 'invitations.accepted-pending-lead',
                    description: "Accepted membership invitation #{$invitation->id} for membership #{$membership->id}; awaiting local Sunday School Lead approval.",
                    subject: $membership,
                    properties: [
                        'invitation_id' => $invitation->id,
                        'school_membership_id' => $membership->id,
                        'organization_id' => $membership->organization_id,
                        'accepted_by_user_id' => $acceptedUser->id,
                    ],
                    request: $request,
                );

                return [
                    'user' => $acceptedUser,
                    'membership' => $membership,
                    'requiresLogin' => $requiresLogin,
                    'auditTechnicalDeny' => null,
                ];
            });
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages([
                'invitation' => [__('messages.demo.invitations.error_pending_lead_review_exists')],
            ]);
        }

        if ($result['auditTechnicalDeny'] !== null) {
            $deniedUser = $result['auditTechnicalDeny'];
            ActivityLogger::record(
                actor: $deniedUser,
                event: 'appointments.technical-incompatibility-denied',
                description: "Denied school membership invitation acceptance for technical identity {$deniedUser->email} (#{$deniedUser->id}).",
                subject: $deniedUser,
                properties: [
                    'target_user_id' => $deniedUser->id,
                    'organization_id' => $result['membership']->organization_id,
                    'school_membership_id' => $result['membership']->id,
                ],
                request: $request,
            );

            throw ValidationException::withMessages([
                'email' => [__('messages.demo.invitations.technical_incompatible')],
            ]);
        }

        // Authenticate and mutate session state ONLY after the database transaction has committed
        if ($result['requiresLogin']) {
            Auth::login($result['user']);
            $request->session()->regenerate();
        }

        if ($request->hasSession()) {
            $request->session()->put(
                HierarchyScopeContext::SESSION_KEY,
                'membership:'.$result['membership']->id
            );
        }

        return to_route('my-profile.show')->with('success', __('messages.demo.flash.invitation_accepted_pending_lead'));
    }
}
