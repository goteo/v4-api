<?php

namespace App\Entity\User;

/**
 * The closed list of roles a User can be assigned.\
 * This is the single source of truth for role names: clients should rely on this list instead of hardcoding their own.
 */
enum UserRole: string
{
    /**
     * Granted to every authenticated User.
     */
    case User = 'ROLE_USER';

    /**
     * Platform administrators.
     */
    case Admin = 'ROLE_ADMIN';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return \array_column(self::cases(), 'value');
    }
}
