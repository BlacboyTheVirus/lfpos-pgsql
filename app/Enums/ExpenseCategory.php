<?php

namespace App\Enums;

enum ExpenseCategory: string
{
    case Miscellaneous = 'miscellaneous';
    case Materials = 'materials';
    case Utilities = 'utilities';
    case Repairs = 'repairs';
    case Cleaning = 'cleaning';
    case Staff = 'staff';

    public function getLabel(): string
    {
        return match ($this) {
            self::Miscellaneous => 'Miscellaneous',
            self::Materials => 'Materials',
            self::Utilities => 'Utilities',
            self::Repairs => 'Repairs & Maintenance',
            self::Cleaning => 'Cleaning',
            self::Staff => 'Staff',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Miscellaneous => 'gray',
            self::Materials => 'blue',
            self::Utilities => 'yellow',
            self::Repairs => 'orange',
            self::Cleaning => 'green',
            self::Staff => 'purple',
        };
    }

    public static function getOptions(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn ($case) => [$case->value => $case->getLabel()])
            ->toArray();
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Miscellaneous => 'heroicon-o-ellipsis-horizontal',
            self::Materials => 'heroicon-o-cube',
            self::Utilities => 'heroicon-o-bolt',
            self::Repairs => 'heroicon-o-wrench-screwdriver',
            self::Cleaning => 'heroicon-o-sparkles',
            self::Staff => 'heroicon-o-users',
        };
    }

    public static function getDefault(): self
    {
        return self::Miscellaneous;
    }
}
