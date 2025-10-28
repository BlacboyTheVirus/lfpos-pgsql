<?php

namespace App\Enums;

enum InvoiceStatus: string
{
    case Unpaid = 'unpaid';
    case Partial = 'partial';
    case Paid = 'paid';

    public function getLabel(): string
    {
        return match ($this) {
            self::Unpaid => 'Unpaid',
            self::Partial => 'Partially Paid',
            self::Paid => 'Paid',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Unpaid => 'danger',
            self::Partial => 'warning',
            self::Paid => 'success',
        };
    }

    public static function getOptions(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn ($case) => [$case->value => $case->getLabel()])
            ->toArray();
    }

    public function getBadgeIcon(): string
    {
        return match ($this) {
            self::Unpaid => 'heroicon-o-exclamation-circle',
            self::Partial => 'heroicon-o-clock',
            self::Paid => 'heroicon-o-check-circle',
        };
    }

    public static function calculateStatus(int $total, int $paid): self
    {
        if ($paid === 0) {
            return self::Unpaid;
        }

        if ($paid >= $total) {
            return self::Paid;
        }

        return self::Partial;
    }
}
