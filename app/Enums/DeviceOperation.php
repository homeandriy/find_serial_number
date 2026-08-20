<?php

namespace App\Enums;

enum DeviceOperation: string
{
    case Receipt = 'receipt';
    case Issue = 'issue';

    public function label(): string
    {
        return match ($this) {
            self::Receipt => 'Прийом',
            self::Issue => 'Видача',
        };
    }
}