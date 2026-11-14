<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MembershipInvitation extends Model
{
    public const STATUS_PENDING_ACCEPTANCE = 'pending_acceptance';

    public const STATUS_PENDING_LEAD_APPROVAL = 'pending_lead_approval';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_REVOKED = 'revoked';

    public const STATUS_EXPIRED = 'expired';

    public const MAIL_PENDING = 'pending';

    public const MAIL_SENT = 'sent';

    public const MAIL_FAILED = 'failed';

    protected $fillable = [
        'organization_id',
        'school_membership_id',
        'token_hash',
        'invited_email',
        'invited_name',
        'status',
        'mail_delivery_status',
        'created_by_user_id',
        'expires_at',
        'mail_sent_at',
        'accepted_at',
        'accepted_by_user_id',
        'revoked_at',
        'revoked_by_user_id',
        'reviewed_by_user_id',
        'reviewed_at',
        'review_note',
        'mail_error',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'mail_sent_at' => 'datetime',
            'accepted_at' => 'datetime',
            'revoked_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function schoolMembership(): BelongsTo
    {
        return $this->belongsTo(SchoolMembership::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function acceptedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accepted_by_user_id');
    }

    public function revokedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by_user_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    public function isExpired(): bool
    {
        return $this->status === self::STATUS_EXPIRED
            || ($this->status === self::STATUS_PENDING_ACCEPTANCE && $this->expires_at !== null && $this->expires_at->isPast());
    }

    public function effectiveStatus(): string
    {
        if ($this->status === self::STATUS_PENDING_ACCEPTANCE && $this->expires_at !== null && $this->expires_at->isPast()) {
            return self::STATUS_EXPIRED;
        }

        return $this->status;
    }
}
