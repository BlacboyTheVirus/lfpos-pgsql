<?php

namespace App\Filament\Resources\Settings\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Setting Information')
                    ->description('Configure system settings and their values')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('name')
                                    ->label('Setting Name')
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true)
                                    ->regex('/^[a-zA-Z0-9_]+$/')
                                    ->validationMessages([
                                        'regex' => 'Setting name can only contain letters, numbers, and underscores.',
                                    ])
                                    ->helperText('Use lowercase letters, numbers, and underscores only (e.g., company_name)')
                                    ->placeholder('company_name')
                                    ->columnSpan(1),

                                TextInput::make('category')
                                    ->label('Category')
                                    ->placeholder('company')
                                    ->helperText('Optional category for organization')
                                    ->maxLength(100)
                                    ->columnSpan(1),
                            ]),

                        Textarea::make('value')
                            ->label('Value')
                            ->required()
                            ->maxLength(65535)
                            ->rows(4)
                            ->helperText('Setting value - can be text, number, JSON, or boolean')
                            ->placeholder('Enter the setting value'),

                        Grid::make(2)
                            ->schema([
                                Toggle::make('is_json')
                                    ->label('JSON Value')
                                    ->helperText('Check if this setting contains JSON data')
                                    ->reactive()
                                    ->columnSpan(1),

                                Toggle::make('is_encrypted')
                                    ->label('Encrypted')
                                    ->helperText('Check if this setting should be encrypted')
                                    ->columnSpan(1),
                            ]),

                        Textarea::make('description')
                            ->label('Description')
                            ->maxLength(500)
                            ->rows(2)
                            ->placeholder('Brief description of what this setting controls')
                            ->helperText('Optional description for documentation purposes'),
                    ]),
            ])
            ->columns(1);
    }

    public static function mutateFormDataBeforeCreate(array $data): array
    {
        return self::processFormData($data);
    }

    public static function mutateFormDataBeforeSave(array $data): array
    {
        return self::processFormData($data);
    }

    public static function getFormComponents(): array
    {
        return [
            Section::make('Setting Information')
                ->description('Configure system settings and their values')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            TextInput::make('name')
                                ->label('Setting Name')
                                ->required()
                                ->maxLength(255)
                                ->unique(ignoreRecord: true)
                                ->regex('/^[a-zA-Z0-9_]+$/')
                                ->validationMessages([
                                    'regex' => 'Setting name can only contain letters, numbers, and underscores.',
                                ])
                                ->helperText('Use lowercase letters, numbers, and underscores only (e.g., company_name)')
                                ->placeholder('company_name')
                                ->columnSpan(1),

                            TextInput::make('category')
                                ->label('Category')
                                ->placeholder('company')
                                ->helperText('Optional category for organization')
                                ->maxLength(100)
                                ->columnSpan(1),
                        ]),

                    Textarea::make('value')
                        ->label('Value')
                        ->required()
                        ->maxLength(65535)
                        ->rows(4)
                        ->helperText('Setting value - can be text, number, JSON, or boolean')
                        ->placeholder('Enter the setting value'),

                    Grid::make(2)
                        ->schema([
                            Toggle::make('is_json')
                                ->label('JSON Value')
                                ->helperText('Check if this setting contains JSON data')
                                ->reactive()
                                ->columnSpan(1),

                            Toggle::make('is_encrypted')
                                ->label('Encrypted')
                                ->helperText('Check if this setting should be encrypted')
                                ->columnSpan(1),
                        ]),

                    Textarea::make('description')
                        ->label('Description')
                        ->maxLength(500)
                        ->rows(2)
                        ->placeholder('Brief description of what this setting controls')
                        ->helperText('Optional description for documentation purposes'),
                ]),
        ];
    }

    private static function processFormData(array $data): array
    {
        // Validate JSON if marked as JSON
        if (isset($data['is_json']) && $data['is_json'] && isset($data['value'])) {
            $decodedValue = json_decode($data['value'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \InvalidArgumentException('Invalid JSON format in value field.');
            }
            // Store as properly formatted JSON
            $data['value'] = json_encode($decodedValue, JSON_PRETTY_PRINT);
        }

        // Convert boolean strings to actual booleans for storage
        if (isset($data['value']) && in_array(strtolower($data['value']), ['true', 'false'])) {
            $data['value'] = strtolower($data['value']) === 'true' ? '1' : '0';
        }

        return $data;
    }
}
