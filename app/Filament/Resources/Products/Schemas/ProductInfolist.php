<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ProductInfolist
{
    public static function configure(Schema $schema): Schema
    {
        // Cache currency settings once to avoid repeated queries
        $currencySettings = \App\Models\Setting::getCurrencySettings();
        $formatMoney = function (int $amountInCents) use ($currencySettings): string {
            $amount = $amountInCents;
            $formatted = number_format(
                $amount,
                (int) $currencySettings['decimal_places'],
                $currencySettings['decimal_separator'] ?? '.',
                $currencySettings['thousands_separator'] ?? ','
            );

            return $currencySettings['currency_position'] === 'before'
                ? $currencySettings['currency_symbol'].$formatted
                : $formatted.$currencySettings['currency_symbol'];
        };

        return $schema
            ->components([
                Section::make('Product Information')
                    ->description('Product details and specifications')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('code')
                                    ->label('Product Code')
                                    ->copyable()
                                    ->copyMessage('Product code copied')
                                    ->badge()
                                    ->color('gray'),

                                TextEntry::make('name')
                                    ->label('Product Name')
                                    ->weight('bold')
                                    ->size('lg'),

                                IconEntry::make('is_active')
                                    ->label('Status')
                                    ->boolean()
                                    ->trueIcon(Heroicon::OutlinedCheckCircle)
                                    ->falseIcon(Heroicon::OutlinedXCircle)
                                    ->trueColor('success')
                                    ->falseColor('danger'),
                            ]),

                        Grid::make(3)
                            ->schema([
                                TextEntry::make('unit')
                                    ->label('Unit of Measurement')
                                    ->badge()
                                    ->color('info'),

                                TextEntry::make('price')
                                    ->label('Unit Price')
                                    ->formatStateUsing(fn ($state) => $formatMoney((int) round($state * 1)))
                                    ->weight('semibold')
                                    ->color('success')
                                    ->alignment('right'),

                                TextEntry::make('minimum_amount')
                                    ->label('Minimum Stock Level')
                                    ->formatStateUsing(fn ($state) => $formatMoney((int) round($state * 1)))
                                    ->badge()
                                    ->color('warning')
                                    ->alignment('right'),
                            ]),

                        TextEntry::make('description')
                            ->label('Description')
                            ->placeholder('No description provided')
                            ->columnSpanFull()
                            ->prose(),
                    ]),

                Section::make('Record Information')
                    ->description('Creation and update details')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('createdBy.name')
                                    ->label('Created By')
                                    ->placeholder('System')
                                    ->badge()
                                    ->color('gray'),

                                TextEntry::make('created_at')
                                    ->label('Created At')
                                    ->dateTime('M j, Y g:i A')
                                    ->since()
                                    ->badge()
                                    ->color('success'),
                            ]),

                        Grid::make(1)
                            ->schema([
                                TextEntry::make('updated_at')
                                    ->label('Last Updated')
                                    ->dateTime('M j, Y g:i A')
                                    ->since()
                                    ->badge()
                                    ->color('info'),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function getInfolistComponents(): array
    {
        return static::configure(Schema::make())->getComponents();
    }
}
