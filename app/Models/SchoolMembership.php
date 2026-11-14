<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchoolMembership extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_SUSPENDED = 'suspended';

    public const STATUS_ARCHIVED = 'archived';

    public const LINK_NONE = 'none';

    public const LINK_PENDING_ACCEPTANCE = 'pending_acceptance';

    public const LINK_PENDING_LEAD_APPROVAL = 'pending_lead_approval';

    public const LINK_APPROVED = 'approved';

    public const LINK_REJECTED = 'rejected';

    /**
     * @var list<string>
     */
    public const ALL_STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_APPROVED,
        self::STATUS_REJECTED,
        self::STATUS_SUSPENDED,
        self::STATUS_ARCHIVED,
    ];

    protected $fillable = [
        'person_id',
        'organization_id',
        'user_id',
        'pending_user_id',
        'membership_code',
        'status',
        'account_link_status',
        'application_note',
        'status_reason',
        'created_by_user_id',
        'approved_by_user_id',
        'approved_at',
        'rejected_by_user_id',
        'rejected_at',
        'suspended_by_user_id',
        'suspended_at',
        'archived_by_user_id',
        'archived_at',
        'joined_on',
        'ended_on',
    ];

    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'suspended_at' => 'datetime',
            'archived_at' => 'datetime',
            'joined_on' => 'date',
            'ended_on' => 'date',
        ];
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function pendingUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pending_user_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by_user_id');
    }

    public function suspendedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'suspended_by_user_id');
    }

    public function archivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'archived_by_user_id');
    }

    public function departmentMemberships(): HasMany
    {
        return $this->hasMany(DepartmentMembership::class);
    }

    public function decisions(): HasMany
    {
        return $this->hasMany(MembershipDecision::class)->orderByDesc('id');
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(MembershipInvitation::class)->orderByDesc('id');
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isBlockedFromAccess(): bool
    {
        return in_array($this->status, [
            self::STATUS_PENDING,
            self::STATUS_REJECTED,
            self::STATUS_SUSPENDED,
            self::STATUS_ARCHIVED,
        ], true);
    }
}
