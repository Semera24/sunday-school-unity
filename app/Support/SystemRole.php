<?php

namespace App\Support;

use App\Models\User;
use Spatie\Permission\Models\Role;

class SystemRole
{
    public const SUPER_ADMIN = 'Super Admin';

    public const ADMIN = 'Admin';

    public const MANAGER = 'Manager';

    public const STAFF = 'Staff';

    public const STANDARD_USER = 'Standard User';

    /**
     * Default application foundation role names that are protected from renaming or deletion.
     *
     * @return list<string>
     */
    public static function names(): array
    {
        return [
            self::SUPER_ADMIN,
            self::ADMIN,
            self::MANAGER,
            self::STAFF,
            self::STANDARD_USER,
        ];
    }

    /**
     * Role names the given actor is permitted to assign.
     *
     * @return list<string>
     */
    public static function assignableRoleNamesFor(?User $actor): array
    {
        if ($actor === null || ! $actor->can('roles.update')) {
            return [];
        }

        $query = Role::query()->orderBy('name');

        if (! $actor->hasRole(self::SUPER_ADMIN)) {
            $query->where('name', '!=', self::SUPER_ADMIN);
        }

        return $query->pluck('name')->values()->all();
    }
}
