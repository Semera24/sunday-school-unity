<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MembershipDecision extends Model
{
    public const ACTION_REGISTERED = 'registered';

    public const ACTION_APPROVED = 'approved';

    public const ACTION_REJECTED = 'rejected';

    public const ACTION_SUSPENDED = 'suspended';

    public const ACTION_ARCHIVED = 'archived';

    public const ACTION_REACTIVATED = 'reactivated';

    public const ACTION_INVITATION_SENT = 'invitation_sent';

    public const ACTION_INVITATION_RESENT = 'invitation_resent';

    public const ACTION_INVITATION_REVOKED = 'invitation_revoked';

    public const ACTION_INVITATION_ACCEPTED_PENDING_LEAD = 'invitation_accepted_pending_lead';

    public const ACTION_ACCOUNT_LINK_APPROVED = 'account_link_approved';

    public const ACTION_ACCOUNT_LINK_REJECTED = 'account_link_rejected';

    protected $fillable = [
        'school_membership_id',
        'organization_id',
        'actor_user_id',
        'target_user_id',
        'action',
        'from_status',
        'to_status',
        'reason',
    ];

    public function schoolMembership(): BelongsTo
    {
        return $this->belongsTo(SchoolMembership::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function targetUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }
}
