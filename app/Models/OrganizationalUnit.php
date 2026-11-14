<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrganizationalUnit extends Model
{
    public const KIND_DEPARTMENT = 'department';

    public const KIND_DIVISION = 'division';

    public const KIND_BOARD = 'board';

    protected $fillable = [
        'organization_id',
        'code',
        'unit_kind',
        'category_code',
        'name_am',
        'name_en',
        'status',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    public function departmentMemberships(): HasMany
    {
        return $this->hasMany(DepartmentMembership::class, 'organizational_unit_id');
    }

    public function demoReports(): HasMany
    {
        return $this->hasMany(DemoReport::class, 'organizational_unit_id');
    }

    public function displayName(?string $locale = null): string
    {
        $locale ??= app()->getLocale();

        if ($locale === 'en' && $this->name_en !== null && $this->name_en !== '') {
            return $this->name_en;
        }

        return $this->name_am;
    }
}
