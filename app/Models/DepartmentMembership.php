<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DepartmentMembership extends Model
{
    protected $fillable = [
        'school_membership_id',
        'organizational_unit_id',
        'role_label_am',
        'starts_on',
        'ends_on',
        'is_active',
        'assigned_by_user_id',
        'ended_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function scopeCurrentlyEffective(Builder $query, ?string $onDate = null): Builder
    {
        $today = $onDate ?? now()->toDateString();

        return $query
            ->where('is_active', true)
            ->where(function (Builder $q) use ($today): void {
                $q->whereNull('starts_on')
                    ->orWhereDate('starts_on', '<=', $today);
            })
            ->where(function (Builder $q) use ($today): void {
                $q->whereNull('ends_on')
                    ->orWhereDate('ends_on', '>=', $today);
            });
    }

    public function isCurrentlyEffective(?string $onDate = null): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $today = $onDate ?? now()->toDateString();
        $startsOn = $this->starts_on?->toDateString();
        $endsOn = $this->ends_on?->toDateString();

        if ($startsOn !== null && $startsOn > $today) {
            return false;
        }

        if ($endsOn !== null && $endsOn < $today) {
            return false;
        }

        return true;
    }

    public function schoolMembership(): BelongsTo
    {
        return $this->belongsTo(SchoolMembership::class);
    }

    public function organizationalUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationalUnit::class);
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by_user_id');
    }

    public function endedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ended_by_user_id');
    }
}
