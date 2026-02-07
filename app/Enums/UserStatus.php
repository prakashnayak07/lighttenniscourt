<?php

namespace App\Enums;

enum UserStatus: string
{
    case Active = 'active';
    case Disabled = 'disabled';
    case Banned = 'banned';
    case Pending = 'pending';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Disabled => 'Disabled',
            self::Banned => 'Banned',
            self::Pending => 'Pending',
        };
    }

    public function canLogin(): bool
    {
        return $this === self::Active;
    }
}
