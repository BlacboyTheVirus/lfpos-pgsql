<?php

namespace App\Filament\Resources\Expenses\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ExpenseInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Expense Information')
                    ->description('Expense details and specifications')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('code')
                                    ->label('Expense Code')
                                    ->copyable()
                                    ->copyMessage('Expense code copied')
                                    ->color('gray'),

                                TextEntry::make('date')
                                    ->label('Expense Date')
                                    ->date('M j, Y'),




                            ]),

                        Grid::make(2)
                            ->schema([

                                TextEntry::make('category')
                                    ->label('Category')
                                    ->badge()
                                    ->color(fn ($state) => $state?->getColor() ?? 'gray'),
                                
                                TextEntry::make('amount')
                                    ->label('Amount')
                                    ->formatStateUsing(fn ($state) => \App\Models\Setting::formatMoney((int) round($state * 1)))
                                    ->weight('semibold')
                                    ->size('lg')
                                    ->color('success')
                                    ->alignment('left'),

                                TextEntry::make('description')
                                    ->label('Description')
                                    ->weight('medium'),
                            ]),

                        TextEntry::make('note')
                            ->label('Additional Notes')
                            ->placeholder('No additional notes')
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
