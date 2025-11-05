<?php

namespace App\Filament\Resources\Settings\Tables;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class SettingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('name')
                    ->label('Setting Name')
                    ->sortable()
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Setting name copied')
                    ->description(fn ($record) => self::getSettingDescription($record->name)),

                TextColumn::make('value')
                    ->label('Value')
                    ->wrap()
                    ->lineClamp(3)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();

                        // Convert arrays to JSON for length checking
                        $stateString = is_array($state) ? json_encode($state, JSON_PRETTY_PRINT) : (string) $state;

                        if (strlen($stateString) <= 100) {
                            return null;
                        }

                        return $stateString;
                    })
                    ->formatStateUsing(function ($state) {
                        if (is_array($state)) {
                            return json_encode($state, JSON_PRETTY_PRINT);
                        }

                        return $state;
                    }),

                TextColumn::make('category')
                    ->label('Category')
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn ($record) => self::getSettingCategory($record->name)),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->since()
                    ->description(fn ($record) => $record->updated_at->format('M j, Y g:i A')),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->label('Category')
                    ->options([
                        'company' => 'Company Information',
                        'bank' => 'Bank Details',
                        'currency' => 'Currency Settings',
                        'invoice' => 'Invoice Settings',
                        'code' => 'Code Generation',
                        'feature' => 'Feature Flags',
                        'business' => 'Business Rules',
                        'ui' => 'UI/UX Settings',
                        'notification' => 'Notifications',
                        'security' => 'Security',
                        'system' => 'System',
                    ])
                    ->query(function ($query, $data) {
                        if (! $data['value']) {
                            return $query;
                        }

                        $category = $data['value'];
                        $patterns = self::getCategoryPatterns();

                        if (isset($patterns[$category])) {
                            return $query->where(function ($query) use ($patterns, $category) {
                                foreach ($patterns[$category] as $pattern) {
                                    $query->orWhere('name', 'like', $pattern);
                                }
                            });
                        }

                        return $query;
                    }),

                TernaryFilter::make('has_json_value')
                    ->label('JSON Values')
                    ->placeholder('All values')
                    ->trueLabel('JSON values only')
                    ->falseLabel('Simple values only')
                    ->queries(
                        true: fn ($query) => $query->where('value', 'like', '%{%')->orWhere('value', 'like', '%[%'),
                        false: fn ($query) => $query->where('value', 'not like', '%{%')->where('value', 'not like', '%[%'),
                    ),
            ])
            ->deferFilters(false)
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->slideOver()
                        ->modalWidth('md')
                        ->color('info')
                        ->infolist(\App\Filament\Resources\Settings\Schemas\SettingInfolist::getInfolistComponents()),

                    EditAction::make()
                        ->slideOver()
                        ->modalWidth('md')
                        ->color('warning')
                        ->form(\App\Filament\Resources\Settings\Schemas\SettingForm::getFormComponents())
                        ->mutateFormDataUsing(fn ($record) => $record->toArray())
                        ->action(function (array $data, $record) {
                            $processedData = \App\Filament\Resources\Settings\Schemas\SettingForm::mutateFormDataBeforeSave($data);
                            $record->update($processedData);
                        })
                        ->successNotificationTitle('Setting updated successfully'),

                    Action::make('reset')
                        ->label('Reset')
                        ->icon(Heroicon::OutlinedArrowPath)
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Reset Setting')
                        ->modalDescription('Are you sure you want to reset this setting to its default value?')
                        ->action(function ($record) {
                            // Reset logic would go here
                            // You could implement default value lookup
                        })
                        ->visible(fn ($record) => self::canReset($record->name)),

                    DeleteAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('Delete Setting')
                        ->modalDescription('Are you sure you want to delete this setting? This action cannot be undone.')
                        ->visible(fn ($record) => self::canDelete($record->name)),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('Delete Selected Settings')
                        ->modalDescription('Are you sure you want to delete the selected settings? This action cannot be undone.'),
                ]),
            ])
            ->defaultSort('name')
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->deferLoading()
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->persistSearchInSession();
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
        $patterns = self::getCategoryPatterns();

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

    private static function getCategoryPatterns(): array
    {
        return [
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

    private static function canReset(string $name): bool
    {
        // Define which settings can be reset
        $resetableSettings = [
            'company_name',
            'company_address',
            'company_phone',
            'company_email',
            'bank_name',
            'bank_account_name',
            'bank_account_number',
            'currency_symbol',
            'auto_round_totals',
            'round_to_nearest',
        ];

        return in_array($name, $resetableSettings) || str_starts_with($name, 'feature_');
    }

    public static function canDelete(string $name): bool
    {
        // Define which settings cannot be deleted (core settings)
        $protectedSettings = [
            'currency_symbol',
            'currency_position',
            'decimal_places',
            'thousands_separator',
            'decimal_separator',
            'customer_code_prefix',
            'product_code_prefix',
            'invoice_code_prefix',
            'expense_code_prefix',
        ];

        return ! in_array($name, $protectedSettings);
    }
}
