<?php

namespace App\Filament\Resources\Customers\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CustomerInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Customer Information')
                    ->description('Customer details and contact information')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('code')
                                    ->label('Customer Code')
                                    ->copyable()
                                    ->copyMessage('Customer code copied'),

                                TextEntry::make('name')
                                    ->label('Customer Name')
                                    ->weight('bold')
                                    ->size('lg'),

                            ]),

                        Grid::make(2)
                            ->schema([
                                TextEntry::make('phone')
                                    ->label('Phone Number')
                                    ->placeholder('No phone provided')
                                    ->copyable()
                                    ->copyMessage('Phone number copied')
                                    ->formatStateUsing(fn ($state) => $state ?: 'Not provided'),

                                TextEntry::make('email')
                                    ->label('Email Address')
                                    ->placeholder('No email provided')
                                    ->copyable()
                                    ->copyMessage('Email copied')
                                    ->formatStateUsing(fn ($state) => $state ?: 'Not provided'),
                            ]),

                        TextEntry::make('address')
                            ->label('Address')
                            ->placeholder('No address provided')
                            ->columnSpanFull()
                            ->prose()
                            ->formatStateUsing(fn ($state) => $state ?: 'No address provided'),
                    ]),

                Section::make('Customer Statistics')
                    ->description('Customer activity and relationship data')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('invoices_count')
                                    ->label('Total Invoices')
                                    ->getStateUsing(fn ($record) => $record->invoices()->count())
                                    ->badge()
                                    ->color('info'),

                                TextEntry::make('display_name')
                                    ->label('Display Name')
                                    ->getStateUsing(fn ($record) => $record->display_name)
                                    ->badge()
                                    ->color('success'),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(),

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
