<?php

namespace App\Filament\Resources\Expenses\Schemas;

use App\Enums\ExpenseCategory;
use DefStudio\SearchableInput\Forms\Components\SearchableInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ExpenseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Expense Information')
                    ->description('Enter the expense details')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('code')
                                    ->label('Expense Code')
                                    ->disabled()
                                    ->dehydrated()
                                    ->default(fn () => \App\Models\Expense::generateNewCode())
                                    ->maxLength(50),

                                Select::make('category')
                                    ->label('Category')
                                    ->options(collect(ExpenseCategory::cases())
                                        ->mapWithKeys(fn ($case) => [$case->value => $case->getLabel()])
                                        ->toArray())
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->placeholder('Select Category'),
                            ]),

                        Grid::make(2)
                            ->schema([
                                DatePicker::make('date')
                                    ->label('Expense Date')
                                    ->required()
                                    ->default(now())
                                    ->maxDate(now())
                                    ->displayFormat('M j, Y')
                                    ->placeholder('Select date'),

                                TextInput::make('amount')
                                    ->label('Amount')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0.01)
                                    ->step(0.01)
                                    ->placeholder('0.00')
                                    ->prefix(\App\Models\Setting::get('currency_symbol', '₦')),

                            ]),

                        SearchableInput::make('description')
                            ->label('Description')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Enter expense description')
                            ->searchUsing(function (string $search) {
                                if (strlen($search) < 2) {
                                    return [];
                                }

                                return \App\Models\Expense::distinctDescriptions($search, 20)
                                    ->pluck('description')
                                    ->toArray();
                            })
                            ->columnSpanFull(),

                        Textarea::make('note')
                            ->label('Additional Notes')
                            ->placeholder('Enter any additional notes (optional)')
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

        // Ensure unique expense code
        if (isset($data['code'])) {
            $counter = 1;

            // Keep generating new codes until we find one that doesn't exist
            while (\App\Models\Expense::where('code', $data['code'])->exists()) {
                $data['code'] = \App\Models\Expense::generateNewCode();
                $counter++;

                // Prevent infinite loop (safety measure)
                if ($counter > 1000) {
                    throw new \Exception('Unable to generate unique expense code after 1000 attempts');
                }
            }
        } else {
            // If no code provided, generate one
            $data['code'] = \App\Models\Expense::generateNewCode();
        }

        return $data;
    }
}
