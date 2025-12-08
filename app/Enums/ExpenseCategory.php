<?php

namespace App\Enums;

enum ExpenseCategory: string
{
    case Miscellaneous = 'miscellaneous';
    case Materials = 'materials';
    case Utilities = 'utilities';
    case RepairsAndCleaning = 'repairs_and_cleaning';
    case Staff = 'staff';
    case Assets = 'assets';

    public function getLabel(): string
    {
        return match ($this) {
            self::Miscellaneous => 'Miscellaneous',
            self::Materials => 'Materials',
            self::Utilities => 'Utilities',
            self::RepairsAndCleaning => 'Repairs & Cleaning',
            self::Staff => 'Staff',
            self::Assets => 'Assets',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Miscellaneous => 'gray',
            self::Materials => 'success',
            self::Utilities => 'warning',
            self::RepairsAndCleaning => 'danger',
            self::Staff => 'info',
            self::Assets => 'cyan',
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
            self::RepairsAndCleaning => 'heroicon-o-wrench-screwdriver',
            self::Staff => 'heroicon-o-users',
            self::Assets => 'heroicon-o-box',
        };
    }

    public static function getDefault(): self
    {
        return self::Materials;
    }
}
