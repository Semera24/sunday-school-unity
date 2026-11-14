<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

class ScopedAppointment extends Model
{
    public const ROLE_UNION_SUPER_ADMIN = 'union_super_admin';

    public const ROLE_SCHOOL_LEAD = 'school_lead';

    public const ROLE_DEPARTMENT_COORDINATOR = 'department_coordinator';

    public const ROLE_DISTRICT_OFFICER = 'district_officer';

    public const ROLE_DIOCESE_OFFICER = 'diocese_officer';

    public const ROLE_UNION_DIVISION_COORDINATOR = 'union_division_coordinator';

    public const ROLE_UNION_BOARD_MEMBER = 'union_board_member';

    public const ROLE_TECHNICAL_OPERATOR = 'technical_operator';

    /**
     * @var list<string>
     */
    public const ALL_ROLES = [
        self::ROLE_UNION_SUPER_ADMIN,
        self::ROLE_SCHOOL_LEAD,
        self::ROLE_DEPARTMENT_COORDINATOR,
        self::ROLE_DISTRICT_OFFICER,
        self::ROLE_DIOCESE_OFFICER,
        self::ROLE_UNION_DIVISION_COORDINATOR,
        self::ROLE_UNION_BOARD_MEMBER,
        self::ROLE_TECHNICAL_OPERATOR,
    ];

    protected $fillable = [
        'user_id',
        'person_id',
        'organization_id',
        'organizational_unit_id',
        'role_key',
        'title_am',
        'title_en',
        'functional_category',
        'starts_on',
        'ends_on',
        'is_active',
    ];

    protected static function booted(): void
    {
        static::saving(function (ScopedAppointment $appointment): void {
            $appointment->assertValidScopeAndDates();
        });
    }

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function scopeEffective(Builder $query): Builder
    {
        $today = now()->toDateString();

        return $query
            ->where('is_active', true)
            ->where(function (Builder $q) use ($today): void {
                $q->whereNull('starts_on')->orWhereDate('starts_on', '<=', $today);
            })
            ->where(function (Builder $q) use ($today): void {
                $q->whereNull('ends_on')->orWhereDate('ends_on', '>=', $today);
            });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'person_id');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    public function organizationalUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationalUnit::class, 'organizational_unit_id');
    }

    public function displayTitle(?string $locale = null): string
    {
        $locale ??= app()->getLocale();

        if ($locale === 'en' && $this->title_en !== null && $this->title_en !== '') {
            return $this->title_en;
        }

        return $this->title_am;
    }

    public function assertValidScopeAndDates(): void
    {
        if (! in_array($this->role_key, self::ALL_ROLES, true)) {
            throw ValidationException::withMessages([
                'role_key' => ['Invalid appointment role code.'],
            ]);
        }

        if ($this->starts_on !== null && $this->ends_on !== null && $this->ends_on->lt($this->starts_on)) {
            throw ValidationException::withMessages([
                'ends_on' => ['Appointment end date cannot precede start date.'],
            ]);
        }

        $targetUser = $this->user_id !== null
            ? User::query()->find($this->user_id)
            : null;

        if ($this->role_key === self::ROLE_TECHNICAL_OPERATOR) {
            if ($this->organization_id !== null || $this->organizational_unit_id !== null) {
                throw ValidationException::withMessages([
                    'organization_id' => ['Technical operator appointment must not be bound to a Sunday School organization.'],
                ]);
            }

            if ($targetUser !== null && $targetUser->hasBusinessIdentity()) {
                throw ValidationException::withMessages([
                    'user_id' => ['Accounts with Sunday School business appointments or memberships cannot be granted technical operator appointments.'],
                ]);
            }

            return;
        }

        if ($targetUser !== null && $targetUser->hasTechnicalIdentity()) {
            throw ValidationException::withMessages([
                'user_id' => ['Technical operator accounts (active or inactive) cannot be granted Sunday School business appointments.'],
            ]);
        }

        $org = $this->organization_id !== null
            ? Organization::query()->find($this->organization_id)
            : null;

        if ($org === null) {
            throw ValidationException::withMessages([
                'organization_id' => ['Scoped appointment requires a valid target organization.'],
            ]);
        }

        $expectedOrgType = match ($this->role_key) {
            self::ROLE_UNION_SUPER_ADMIN,
            self::ROLE_UNION_DIVISION_COORDINATOR,
            self::ROLE_UNION_BOARD_MEMBER => Organization::TYPE_UNION,
            self::ROLE_DIOCESE_OFFICER => Organization::TYPE_DIOCESE,
            self::ROLE_DISTRICT_OFFICER => Organization::TYPE_DISTRICT,
            self::ROLE_SCHOOL_LEAD,
            self::ROLE_DEPARTMENT_COORDINATOR => Organization::TYPE_SUNDAY_SCHOOL,
            default => null,
        };

        if ($expectedOrgType !== null && $org->type !== $expectedOrgType) {
            throw ValidationException::withMessages([
                'organization_id' => ["Role {$this->role_key} requires an organization of type {$expectedOrgType}."],
            ]);
        }

        if ($this->role_key === self::ROLE_SCHOOL_LEAD && $this->organizational_unit_id !== null) {
            throw ValidationException::withMessages([
                'organizational_unit_id' => ['Sunday School lead appointment applies to the school scope, not a single department.'],
            ]);
        }

        if ($this->role_key === self::ROLE_DEPARTMENT_COORDINATOR) {
            $unit = $this->organizational_unit_id !== null
                ? OrganizationalUnit::query()->find($this->organizational_unit_id)
                : null;

            if ($unit === null
                || (int) $unit->organization_id !== (int) $org->id
                || $unit->unit_kind !== OrganizationalUnit::KIND_DEPARTMENT
            ) {
                throw ValidationException::withMessages([
                    'organizational_unit_id' => ['Department coordinator appointment requires a valid department belonging to the selected Sunday School.'],
                ]);
            }
        }
    }
}
