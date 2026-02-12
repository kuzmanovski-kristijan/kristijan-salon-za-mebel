<?php

namespace App\Support;

use App\Models\User;

final class AdminAccess
{
    public static function isAdmin(?User $user): bool
    {
        return $user && method_exists($user, 'hasRole') && $user->hasRole('admin');
    }

    public static function isStaff(?User $user): bool
    {
        return $user && method_exists($user, 'hasAnyRole') && $user->hasAnyRole(['admin', 'staff']);
    }
}

