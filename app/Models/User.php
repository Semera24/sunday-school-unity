<?php

namespace App\Models;

use App\Support\Locales;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'locale', 'is_business_account', 'password', 'email_verified_at'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable, TwoFactorAuthenticatable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_business_account' => 'boolean',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    /**
     * Resolve the effective locale for the user.
     */
    public function effectiveLocale(): string
    {
        return Locales::isSupported($this->locale)
            ? $this->locale
            : Locales::defaultCode();
    }

    public function scopedAppointments(): HasMany
    {
        return $this->hasMany(ScopedAppointment::class);
    }

    public function schoolMemberships(): HasMany
    {
        return $this->hasMany(SchoolMembership::class);
    }

    public function pendingSchoolMemberships(): HasMany
    {
        return $this->hasMany(SchoolMembership::class, 'pending_user_id');
    }

    public function acceptedMembershipInvitations(): HasMany
    {
        return $this->hasMany(MembershipInvitation::class, 'accepted_by_user_id');
    }

    /**
     * Determine whether this account holds any technical operator role or appointment.
     */
    public function hasTechnicalIdentity(): bool
    {
        if ($this->roles()->exists()) {
            return true;
        }

        return $this->scopedAppointments()
            ->where('role_key', ScopedAppointment::ROLE_TECHNICAL_OPERATOR)
            ->exists();
    }

    /**
     * Determine whether this account holds any church/business appointment, membership, or invited business identity.
     *
     * Technical operators must not modify credentials, roles, or profile data for business identities,
     * nor may business identities be granted technical operator roles.
     * Including is_business_account, pendingSchoolMemberships, and acceptedMembershipInvitations protects
     * newly created invitation-only accounts even before their membership link is approved by a school lead.
     */
    public function hasBusinessIdentity(): bool
    {
        if ($this->is_business_account) {
            return true;
        }

        if (
            $this->scopedAppointments()
                ->where('role_key', '!=', ScopedAppointment::ROLE_TECHNICAL_OPERATOR)
                ->exists()
        ) {
            return true;
        }

        if ($this->schoolMemberships()->exists()) {
            return true;
        }

        if ($this->pendingSchoolMemberships()->exists()) {
            return true;
        }

        return $this->acceptedMembershipInvitations()->exists();
    }

    /**
     * Scope a query to technical-only accounts (excluding all business/member/invited accounts).
     */
    public function scopeTechnicalOnly(Builder $query): Builder
    {
        return $query
            ->where('is_business_account', false)
            ->whereDoesntHave('schoolMemberships')
            ->whereDoesntHave('pendingSchoolMemberships')
            ->whereDoesntHave('acceptedMembershipInvitations')
            ->whereDoesntHave(
                'scopedAppointments',
                fn (Builder $q) => $q->where('role_key', '!=', ScopedAppointment::ROLE_TECHNICAL_OPERATOR)
            );
    }
}
