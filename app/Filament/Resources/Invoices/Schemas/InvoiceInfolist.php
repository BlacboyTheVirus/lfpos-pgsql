<?php

namespace App\Filament\Resources\Invoices\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class InvoiceInfolist
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
                Section::make('Invoice Information')
                    ->description('Invoice details and customer information')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('code')
                                    ->label('Invoice Code')
                                    ->copyable()
                                    ->copyMessage('Invoice code copied')
                                    ->weight('bold'),

                                TextEntry::make('customer.name')
                                    ->label('Customer')
                                    ->weight('bold'),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextEntry::make('customer.phone')
                                    ->label('Customer Phone')
                                    ->placeholder('No phone provided')
                                    ->copyable()
                                    ->copyMessage('Phone number copied'),

                                TextEntry::make('customer.email')
                                    ->label('Customer Email')
                                    ->placeholder('No email provided')
                                    ->copyable()
                                    ->copyMessage('Email copied'),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextEntry::make('date')
                                    ->label('Invoice Date')
                                    ->date('M j, Y')
                                    ->weight('medium'),

                                TextEntry::make('status')
                                    ->label('Status')
                                    ->formatStateUsing(fn ($state) => $state?->getLabel() ?? $state)
                                    ->badge()
                                    ->color(fn ($state) => $state?->getColor() ?? 'gray'),
                            ]),
                    ]),

                Section::make('Amount Breakdown')
                    ->description('Financial details and calculations')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('subtotal')
                                    ->label('Subtotal')
                                    ->formatStateUsing(fn ($state) => $formatMoney((int) round($state * 1)))
                                    ->weight('medium')
                                    ->alignment('right'),

                                TextEntry::make('discount')
                                    ->label('Discount')
                                    ->formatStateUsing(fn ($state) => $formatMoney((int) round($state * 1)))
                                    ->color('warning')
                                    ->alignment('right'),

                                TextEntry::make('round_off')
                                    ->label('Round Off')
                                    ->formatStateUsing(fn ($state) => $formatMoney((int) round($state * 1)))
                                    ->color('info')
                                    ->alignment('right'),
                            ]),

                        Grid::make(3)
                            ->schema([
                                TextEntry::make('total')
                                    ->label('Total Amount')
                                    ->formatStateUsing(fn ($state) => $formatMoney((int) round($state * 1)))
                                    ->weight('bold')
                                    ->size('lg')
                                    ->color('success')
                                    ->alignment('right'),

                                TextEntry::make('paid')
                                    ->label('Paid Amount')
                                    ->formatStateUsing(fn ($state) => $formatMoney((int) round($state * 1)))
                                    ->weight('medium')
                                    ->color('success')
                                    ->alignment('right'),

                                TextEntry::make('due')
                                    ->label('Due Amount')
                                    ->formatStateUsing(fn ($state) => $formatMoney((int) round($state * 1)))
                                    ->weight('medium')
                                    ->color(fn ($state) => $state > 0 ? 'danger' : 'success')
                                    ->alignment('right'),
                            ]),
                    ]),

                Section::make('Payment Status')
                    ->description('Payment tracking and status')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                IconEntry::make('is_paid')
                                    ->label('Payment Status')
                                    ->boolean()
                                    ->trueIcon(Heroicon::OutlinedCheckCircle)
                                    ->falseIcon(Heroicon::OutlinedXCircle)
                                    ->trueColor('success')
                                    ->falseColor('danger'),

                                TextEntry::make('total_in_words')
                                    ->label('Amount in Words')
                                    ->getStateUsing(fn ($record) => $record->getTotalInWords())
                                    ->prose()
                                    ->color('gray'),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(),

                Section::make('Additional Information')
                    ->description('Notes and other details')
                    ->schema([
                        TextEntry::make('note')
                            ->label('Notes')
                            ->placeholder('No notes provided')
                            ->prose()
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed()
                    ->visible(fn ($record) => ! empty($record->note)),

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
