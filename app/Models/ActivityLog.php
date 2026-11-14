<?php

namespace App\Models;

use Database\Factories\ActivityLogFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActivityLog extends Model
{
    /** @use HasFactory<ActivityLogFactory> */
    use HasFactory;

    /**
     * Private school/member/invitation/department/draft audit events that must not be exposed to technical operators.
     *
     * @var list<string>
     */
    public const PRIVATE_SCHOOL_EVENTS = [
        'member-profile.updated',
        'memberships.registered',
        'memberships.updated',
        'memberships.approved',
        'memberships.rejected',
        'memberships.suspended',
        'memberships.archived',
        'memberships.reactivated',
        'invitations.created',
        'invitations.resent',
        'invitations.mail-failed',
        'invitations.revoked',
        'invitations.accepted-pending-lead',
        'invitations.link-approved',
        'invitations.link-rejected',
        'department-memberships.assigned',
        'department-memberships.reassigned',
        'department-memberships.updated',
        'reports.submitted-local',
        'departments.created',
    ];

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'actor_id',
        'event',
        'description',
        'subject_type',
        'subject_id',
        'properties',
        'ip_address',
        'created_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'properties' => 'array',
            'created_at' => 'immutable_datetime',
        ];
    }

    /**
     * Get the actor who triggered the log entry.
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /**
     * Get the related subject model.
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Exclude private school/member/invitation/department/draft audit events from technical operator logs.
     */
    public function scopeExcludePrivateSchoolEvents(Builder $query): Builder
    {
        return $query->whereNotIn('event', self::PRIVATE_SCHOOL_EVENTS);
    }
}
