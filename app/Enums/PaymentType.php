<?php

namespace App\Enums;

enum PaymentType: string
{
    case Cash = 'cash';
    case Transfer = 'transfer';
    case POS = 'pos';

    public function getLabel(): string
    {
        return match ($this) {
            self::Cash => 'Cash',
            self::Transfer => 'Bank Transfer',
            self::POS => 'POS Terminal',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Cash => 'success',
            self::Transfer => 'info',
            self::POS => 'warning',
        };
    }

    public static function getOptions(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn ($case) => [$case->value => $case->getLabel()])
            ->toArray();
    }

    public static function getDefault(): self
    {
        return self::Cash;
    }
}
