<?php

namespace App\Enums;

enum ProductDefault: string
{
    case SAV = 'sav';
    case FLEX = 'flex';
    case TRANSPARENT = 'transparent';

    public function getLabel(): string
    {
        return match ($this) {
            self::SAV => 'SAV',
            self::FLEX => 'FLEX',
            self::TRANSPARENT => 'Transparent',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::SAV => 'Self-Adhesive Vinyl',
            self::FLEX => 'Flexible Banner Material',
            self::TRANSPARENT => 'Transparent Vinyl',
        };
    }

    public static function getOptions(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn ($case) => [$case->value => $case->getLabel()])
            ->toArray();
    }

    public function getDefaultDimensions(): array
    {
        return match ($this) {
            self::SAV => [
                'width' => 1.0,
                'height' => 1.0,
                'unit' => 'sqm',
            ],
            self::FLEX => [
                'width' => 2.0,
                'height' => 1.0,
                'unit' => 'sqm',
            ],
            self::TRANSPARENT => [
                'width' => 1.0,
                'height' => 1.0,
                'unit' => 'sqm',
            ],
        };
    }

    public function getTypicalPrice(): int
    {
        // Prices in cents (₦)
        return match ($this) {
            self::SAV => 150000, // ₦1,500 per sqm
            self::FLEX => 120000, // ₦1,200 per sqm
            self::TRANSPARENT => 200000, // ₦2,000 per sqm
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::SAV => 'blue',
            self::FLEX => 'green',
            self::TRANSPARENT => 'gray',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::SAV => 'heroicon-o-rectangle-stack',
            self::FLEX => 'heroicon-o-photo',
            self::TRANSPARENT => 'heroicon-o-eye',
        };
    }
}
