<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DemoReport extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_SUBMITTED_LOCAL = 'submitted_local';

    public const STATUS_RELEASED = 'released';

    protected $fillable = [
        'organization_id',
        'organizational_unit_id',
        'category_code',
        'period_label',
        'title_am',
        'summary_am',
        'activity_count',
        'status',
        'submitted_by_user_id',
        'approved_by_user_id',
        'released_at',
    ];

    protected function casts(): array
    {
        return [
            'activity_count' => 'integer',
            'released_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    public function organizationalUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationalUnit::class, 'organizational_unit_id');
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by_user_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }
}
