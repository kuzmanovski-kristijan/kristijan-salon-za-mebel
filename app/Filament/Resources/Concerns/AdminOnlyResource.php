<?php

namespace App\Filament\Resources\Concerns;

use App\Models\User;
use App\Support\AdminAccess;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;

trait AdminOnlyResource
{
    protected static function adminAccessUser(): ?User
    {
        $user = Filament::auth()->user();

        return $user instanceof User ? $user : null;
    }

    public static function canViewAny(): bool
    {
        return AdminAccess::isAdmin(static::adminAccessUser());
    }

    public static function canView(Model $record): bool
    {
        return AdminAccess::isAdmin(static::adminAccessUser());
    }

    public static function canCreate(): bool
    {
        return AdminAccess::isAdmin(static::adminAccessUser());
    }

    public static function canEdit(Model $record): bool
    {
        return AdminAccess::isAdmin(static::adminAccessUser());
    }

    public static function canDelete(Model $record): bool
    {
        return AdminAccess::isAdmin(static::adminAccessUser());
    }

    public static function canDeleteAny(): bool
    {
        return AdminAccess::isAdmin(static::adminAccessUser());
    }

    public static function canForceDelete(Model $record): bool
    {
        return AdminAccess::isAdmin(static::adminAccessUser());
    }

    public static function canForceDeleteAny(): bool
    {
        return AdminAccess::isAdmin(static::adminAccessUser());
    }

    public static function canRestore(Model $record): bool
    {
        return AdminAccess::isAdmin(static::adminAccessUser());
    }

    public static function canRestoreAny(): bool
    {
        return AdminAccess::isAdmin(static::adminAccessUser());
    }

    public static function canReplicate(Model $record): bool
    {
        return AdminAccess::isAdmin(static::adminAccessUser());
    }

    public static function canReorder(): bool
    {
        return AdminAccess::isAdmin(static::adminAccessUser());
    }
}

