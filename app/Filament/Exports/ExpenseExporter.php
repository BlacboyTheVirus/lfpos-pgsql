<?php

namespace App\Filament\Exports;

use App\Models\Expense;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Database\Eloquent\Builder;

class ExpenseExporter extends Exporter
{
    protected static ?string $model = Expense::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('code')
                ->label('Expense Code'),

            ExportColumn::make('date')
                ->label('Date')
                ->formatStateUsing(fn ($state) => $state?->format('Y-m-d')),

            ExportColumn::make('category')
                ->label('Category')
                ->formatStateUsing(fn ($state) => $state?->getLabel()),

            ExportColumn::make('description')
                ->label('Description'),

            ExportColumn::make('amount')
                ->label('Amount')
                ->formatStateUsing(function ($state) {
                    // For CSV/Excel: return as plain number without currency symbols or commas
                    return $state ? number_format($state, 2, '.', '') : '0.00';
                }),

            ExportColumn::make('note')
                ->label('Notes'),

            ExportColumn::make('createdBy.name')
                ->label('Created By'),

            ExportColumn::make('created_at')
                ->label('Created At')
                ->formatStateUsing(fn ($state) => $state?->format('Y-m-d H:i:s')),
        ];
    }

    public static function modifyQuery(Builder $query): Builder
    {
        return $query->with(['createdBy']);
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your expense export has completed and '.number_format($export->successful_rows).' '.str('row')->plural($export->successful_rows).' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to export.';
        }

        return $body;
    }
}
