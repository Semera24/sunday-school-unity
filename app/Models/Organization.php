<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

class Organization extends Model
{
    public const TYPE_UNION = 'union';

    public const TYPE_DIOCESE = 'diocese';

    public const TYPE_DISTRICT = 'district';

    public const TYPE_SUNDAY_SCHOOL = 'sunday_school';

    /**
     * Allowed parent type for each organization type in the demo hierarchy.
     *
     * @var array<string, list<string>>
     */
    public const ALLOWED_PARENT_TYPES = [
        self::TYPE_UNION => [],
        self::TYPE_DIOCESE => [self::TYPE_UNION],
        self::TYPE_DISTRICT => [self::TYPE_DIOCESE],
        self::TYPE_SUNDAY_SCHOOL => [self::TYPE_DISTRICT, self::TYPE_DIOCESE],
    ];

    protected $fillable = [
        'parent_id',
        'code',
        'type',
        'name_am',
        'name_en',
        'city_am',
        'city_en',
        'parish_name_am',
        'parish_name_en',
        'status',
    ];

    protected static function booted(): void
    {
        static::saving(function (Organization $organization): void {
            $organization->assertValidHierarchy();
        });
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('id');
    }

    public function units(): HasMany
    {
        return $this->hasMany(OrganizationalUnit::class, 'organization_id')->orderBy('id');
    }

    public function schoolMemberships(): HasMany
    {
        return $this->hasMany(SchoolMembership::class, 'organization_id');
    }

    public function demoReports(): HasMany
    {
        return $this->hasMany(DemoReport::class, 'organization_id');
    }

    public function displayName(?string $locale = null): string
    {
        $locale ??= app()->getLocale();

        if ($locale === 'en' && $this->name_en !== null && $this->name_en !== '') {
            return $this->name_en;
        }

        return $this->name_am;
    }

    /**
     * Return all ancestor IDs from immediate parent up to root, guarding against cycles.
     *
     * @return list<int>
     */
    public function ancestorIds(): array
    {
        $ancestors = [];
        $visited = $this->exists ? [$this->id => true] : [];
        $currentId = $this->parent_id;

        while ($currentId !== null) {
            if (isset($visited[$currentId])) {
                break;
            }
            $visited[$currentId] = true;
            $ancestors[] = (int) $currentId;
            $currentId = self::query()->whereKey($currentId)->value('parent_id');
        }

        return $ancestors;
    }

    /**
     * Return this organization ID plus all descendant organization IDs.
     *
     * @return list<int>
     */
    public function subtreeIds(): array
    {
        $all = self::query()->select(['id', 'parent_id'])->get();
        $childrenByParent = [];

        foreach ($all as $row) {
            if ($row->parent_id !== null) {
                $childrenByParent[(int) $row->parent_id][] = (int) $row->id;
            }
        }

        $result = [];
        $queue = [(int) $this->id];
        $visited = [];

        while ($queue !== []) {
            $current = array_shift($queue);
            if (isset($visited[$current])) {
                continue;
            }
            $visited[$current] = true;
            $result[] = $current;

            foreach ($childrenByParent[$current] ?? [] as $childId) {
                if (! isset($visited[$childId])) {
                    $queue[] = $childId;
                }
            }
        }

        return $result;
    }

    public function isWithinJurisdictionOf(self $jurisdictionRoot): bool
    {
        if ((int) $this->id === (int) $jurisdictionRoot->id) {
            return true;
        }

        return in_array((int) $jurisdictionRoot->id, $this->ancestorIds(), true);
    }

    public function assertValidHierarchy(): void
    {
        if (! array_key_exists($this->type, self::ALLOWED_PARENT_TYPES)) {
            throw ValidationException::withMessages([
                'type' => ['Invalid organization type.'],
            ]);
        }

        if ($this->type === self::TYPE_UNION) {
            if ($this->parent_id !== null) {
                throw ValidationException::withMessages([
                    'parent_id' => ['Union organization cannot have a parent organization.'],
                ]);
            }

            return;
        }

        if ($this->parent_id === null) {
            throw ValidationException::withMessages([
                'parent_id' => ['Non-union organizations require a valid parent organization.'],
            ]);
        }

        if ($this->exists && (int) $this->parent_id === (int) $this->id) {
            throw ValidationException::withMessages([
                'parent_id' => ['An organization cannot be its own parent.'],
            ]);
        }

        $parent = self::query()->find($this->parent_id);

        if ($parent === null) {
            throw ValidationException::withMessages([
                'parent_id' => ['Selected parent organization does not exist.'],
            ]);
        }

        if ($this->exists && in_array((int) $this->id, $parent->ancestorIds(), true)) {
            throw ValidationException::withMessages([
                'parent_id' => ['Hierarchy cycle detected: parent cannot be a descendant of this organization.'],
            ]);
        }

        $allowedParents = self::ALLOWED_PARENT_TYPES[$this->type];
        if (! in_array($parent->type, $allowedParents, true)) {
            throw ValidationException::withMessages([
                'parent_id' => ["Organization of type {$this->type} cannot belong to parent of type {$parent->type}."],
            ]);
        }
    }
}
