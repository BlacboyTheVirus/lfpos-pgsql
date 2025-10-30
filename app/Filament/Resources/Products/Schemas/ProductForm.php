<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Product Information')
                    ->description('Enter the basic product details')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('code')
                                    ->label('Product Code')
                                    ->disabled()
                                    ->dehydrated()
                                    ->default(fn () => \App\Models\Product::generateNewCode())
                                    ->maxLength(50),

                                TextInput::make('name')
                                    ->label('Product Name')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Enter product name'),
                            ]),

                        Grid::make(2)
                            ->schema([
                                Select::make('unit')
                                    ->label('Unit')
                                    ->required()
                                    ->options([
                                        'sqft' => 'Square Feet',
                                        'pcs' => 'Pieces',
                                        'kg' => 'Kilograms',
                                        'g' => 'Grams',
                                        'l' => 'Liters',
                                        'ml' => 'Milliliters',
                                        'm' => 'Meters',
                                        'cm' => 'Centimeters',
                                        'sqm' => 'Square Meters',
                                        'box' => 'Box',
                                        'pack' => 'Pack',
                                        'set' => 'Set',
                                        'pair' => 'Pair',
                                        'dozen' => 'Dozen',
                                    ])
                                    ->default('pcs')
                                    ->searchable(),

                                Toggle::make('is_active')
                                    ->label('Active Status')
                                    ->default(true)
                                    ->inline(false),

                                TextInput::make('price')
                                    ->label('Unit Price')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0)
                                    ->step(0.01)
                                    ->placeholder('0.00')
                                    ->prefix(\App\Models\Setting::get('currency_symbol', '₦')),

                                TextInput::make('minimum_amount')
                                    ->label('Minimum Amount')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0)
                                    ->step(0.01)
                                    ->default(0)
                                    ->placeholder('0.00')
                                    ->prefix(\App\Models\Setting::get('currency_symbol', '₦')),
                            ]),

                        Textarea::make('description')
                            ->label('Description')
                            ->placeholder('Enter product description (optional)')
                            ->maxLength(1000)
                            ->rows(3)
                            ->columnSpanFull(),

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

        // Ensure unique product code
        if (isset($data['code'])) {
            $originalCode = $data['code'];
            $counter = 1;

            // Keep generating new codes until we find one that doesn't exist
            while (\App\Models\Product::where('code', $data['code'])->exists()) {
                $data['code'] = \App\Models\Product::generateNewCode();
                $counter++;

                // Prevent infinite loop (safety measure)
                if ($counter > 1000) {
                    throw new \Exception('Unable to generate unique product code after 1000 attempts');
                }
            }
        } else {
            // If no code provided, generate one
            $data['code'] = \App\Models\Product::generateNewCode();
        }

        return $data;
    }
}
