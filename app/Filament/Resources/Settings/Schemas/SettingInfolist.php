<?php

namespace App\Filament\Resources\Settings\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SettingInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Setting Details')
                    ->description('View setting information and configuration')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('name')
                                    ->label('Setting Name')
                                    ->copyable()
                                    ->copyMessage('Setting name copied')
                                    ->badge()
                                    ->color('primary')
                                    ->columnSpan(1),

                                TextEntry::make('category')
                                    ->label('Category')
                                    ->badge()
                                    ->color('info')
                                    ->formatStateUsing(fn ($record) => self::getSettingCategory($record->name))
                                    ->columnSpan(1),
                            ]),

                        TextEntry::make('value')
                            ->label('Current Value')
                            ->formatStateUsing(function ($state) {
                                if (is_array($state)) {
                                    return json_encode($state, JSON_PRETTY_PRINT);
                                }

                                return $state;
                            })
                            ->badge()
                            ->color(fn ($state) => self::getValueColor($state))
                            ->copyable()
                            ->copyMessage('Value copied')
                            ->columnSpanFull(),

                        Grid::make(3)
                            ->schema([
                                TextEntry::make('is_json')
                                    ->label('JSON Value')
                                    ->formatStateUsing(fn ($state) => $state ? 'Yes' : 'No')
                                    ->badge()
                                    ->color(fn ($state) => $state ? 'success' : 'gray')
                                    ->columnSpan(1),

                                TextEntry::make('is_encrypted')
                                    ->label('Encrypted')
                                    ->formatStateUsing(fn ($state) => $state ? 'Yes' : 'No')
                                    ->badge()
                                    ->color(fn ($state) => $state ? 'warning' : 'gray')
                                    ->columnSpan(1),

                                TextEntry::make('created_at')
                                    ->label('Created')
                                    ->dateTime('M j, Y g:i A')
                                    ->columnSpan(1),
                            ]),

                        TextEntry::make('description')
                            ->label('Description')
                            ->placeholder('No description provided')
                            ->formatStateUsing(fn ($record) => $record->description ?: self::getSettingDescription($record->name)
                            )
                            ->columnSpanFull(),

                        TextEntry::make('updated_at')
                            ->label('Last Updated')
                            ->dateTime('M j, Y g:i A')
                            ->since()
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function getInfolistComponents(): array
    {
        return [
            Section::make('Setting Details')
                ->description('View setting information and configuration')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            TextEntry::make('name')
                                ->label('Setting Name')
                                ->copyable()
                                ->copyMessage('Setting name copied')
                                ->badge()
                                ->color('primary')
                                ->columnSpan(1),

                            TextEntry::make('category')
                                ->label('Category')
                                ->badge()
                                ->color('info')
                                ->formatStateUsing(fn ($record) => self::getSettingCategory($record->name))
                                ->columnSpan(1),
                        ]),

                    TextEntry::make('value')
                        ->label('Current Value')
                        ->formatStateUsing(function ($state) {
                            if (is_array($state)) {
                                return json_encode($state, JSON_PRETTY_PRINT);
                            }

                            return $state;
                        })
                        ->badge()
                        ->color(fn ($state) => self::getValueColor($state))
                        ->copyable()
                        ->copyMessage('Value copied')
                        ->columnSpanFull(),

                    Grid::make(3)
                        ->schema([
                            TextEntry::make('is_json')
                                ->label('JSON Value')
                                ->formatStateUsing(fn ($state) => $state ? 'Yes' : 'No')
                                ->badge()
                                ->color(fn ($state) => $state ? 'success' : 'gray')
                                ->columnSpan(1),

                            TextEntry::make('is_encrypted')
                                ->label('Encrypted')
                                ->formatStateUsing(fn ($state) => $state ? 'Yes' : 'No')
                                ->badge()
                                ->color(fn ($state) => $state ? 'warning' : 'gray')
                                ->columnSpan(1),

                            TextEntry::make('created_at')
                                ->label('Created')
                                ->dateTime('M j, Y g:i A')
                                ->columnSpan(1),
                        ]),

                    TextEntry::make('description')
                        ->label('Description')
                        ->placeholder('No description provided')
                        ->formatStateUsing(fn ($record) => $record->description ?: self::getSettingDescription($record->name)
                        )
                        ->columnSpanFull(),

                    TextEntry::make('updated_at')
                        ->label('Last Updated')
                        ->dateTime('M j, Y g:i A')
                        ->since()
                        ->columnSpanFull(),
                ]),
        ];
    }

    private static function getSettingDescription(string $name): string
    {
        $descriptions = [
            'company_name' => 'The name of your business',
            'company_address' => 'Your business address',
            'company_phone' => 'Primary contact phone number',
            'company_email' => 'Primary contact email address',
            'bank_name' => 'Your bank name for invoices',
            'bank_account_name' => 'Account holder name',
            'bank_account_number' => 'Bank account number',
            'customer_code_prefix' => 'Prefix for customer codes (e.g., CU-)',
            'product_code_prefix' => 'Prefix for product codes (e.g., PR-)',
            'invoice_code_prefix' => 'Prefix for invoice codes (e.g., IN-)',
            'expense_code_prefix' => 'Prefix for expense codes (e.g., EX-)',
            'currency_symbol' => 'Currency symbol to display',
            'auto_round_totals' => 'Automatically round invoice totals',
            'round_to_nearest' => 'Round totals to nearest amount',
        ];

        return $descriptions[$name] ?? 'Custom setting configuration';
    }

    private static function getSettingCategory(string $name): string
    {
        $patterns = [
            'company' => ['company_%'],
            'bank' => ['bank_%'],
            'currency' => ['currency_%'],
            'invoice' => ['invoice_%'],
            'code' => ['%_code_%', 'next_code_%'],
            'feature' => ['feature_%'],
            'business' => ['min_%', 'max_%', 'default_%', 'allow_%', 'require_%'],
            'ui' => ['enable_%', 'show_%', 'compact_%', 'auto_save_%'],
            'notification' => ['notify_%', 'email_%', 'sms_%'],
            'security' => ['session_%', 'password_%', 'max_login_%', 'lockout_%', 'require_two_%', 'audit_%'],
            'system' => ['app_%', 'cache_%', 'maintenance_%', 'database_%', 'last_%'],
        ];

        foreach ($patterns as $category => $categoryPatterns) {
            foreach ($categoryPatterns as $pattern) {
                if (fnmatch(str_replace('%', '*', $pattern), $name)) {
                    $categoryLabels = [
                        'company' => 'Company',
                        'bank' => 'Banking',
                        'currency' => 'Currency',
                        'invoice' => 'Invoice',
                        'code' => 'Codes',
                        'feature' => 'Features',
                        'business' => 'Business',
                        'ui' => 'Interface',
                        'notification' => 'Notifications',
                        'security' => 'Security',
                        'system' => 'System',
                    ];

                    return $categoryLabels[$category] ?? 'Other';
                }
            }
        }

        return 'Other';
    }

    private static function getValueColor($state): string
    {
        if (is_bool($state) || in_array($state, ['true', 'false', '1', '0'])) {
            return $state ? 'success' : 'danger';
        }

        if (is_numeric($state)) {
            return 'info';
        }

        if (is_array($state) || (is_string($state) && (str_starts_with($state, '{') || str_starts_with($state, '[')))) {
            return 'warning';
        }

        return 'gray';
    }
}
