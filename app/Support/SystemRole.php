<?php

namespace App\Support;

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
}
