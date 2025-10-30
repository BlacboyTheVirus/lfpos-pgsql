<?php

namespace App\Filament\Resources\Customers\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Customer Information')
                    ->description('Enter the customer details')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('code')
                                    ->label('Customer Code')
                                    ->disabled()
                                    ->dehydrated()
                                    ->default(fn () => \App\Models\Customer::generateNewCode())
                                    ->helperText('Auto-generated using configured prefix')
                                    ->maxLength(50),

                                TextInput::make('name')
                                    ->label('Customer Name')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Enter customer name'),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('phone')
                                    ->label('Phone Number')
                                    ->tel()
                                    ->maxLength(20)
                                    ->placeholder('Enter phone number')
                                    ->helperText('Optional - for contact purposes'),

                                TextInput::make('email')
                                    ->label('Email Address')
                                    ->email()
                                    ->maxLength(255)
                                    ->placeholder('Enter email address')
                                    ->helperText('Optional - for invoices and notifications')
                                    ->unique(ignoreRecord: true),
                            ]),

                        Textarea::make('address')
                            ->label('Address')
                            ->placeholder('Enter customer address (optional)')
                            ->maxLength(1000)
                            ->rows(3)
                            ->columnSpanFull()
                            ->helperText('Full address including street, city, state, postal code'),
                    ]),
            ]);
    }

    public static function getFormComponents(): array
    {
        return static::configure(Schema::make())->getComponents();
    }

    public static function mutateFormDataBeforeSave(array $data): array
    {
        // Remove created_by as it's handled automatically by the model
        unset($data['created_by']);

        // Ensure unique customer code
        if (isset($data['code'])) {
            $counter = 1;

            // Keep generating new codes until we find one that doesn't exist
            while (\App\Models\Customer::where('code', $data['code'])->exists()) {
                $data['code'] = \App\Models\Customer::generateNewCode();
                $counter++;

                // Prevent infinite loop (safety measure)
                if ($counter > 1000) {
                    throw new \Exception('Unable to generate unique customer code after 1000 attempts');
                }
            }
        } else {
            // If no code provided, generate one
            $data['code'] = \App\Models\Customer::generateNewCode();
        }

        return $data;
    }
}
