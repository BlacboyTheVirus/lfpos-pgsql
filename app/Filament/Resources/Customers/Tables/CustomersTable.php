<?php

namespace App\Filament\Resources\Customers\Tables;

use App\Models\Setting;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class CustomersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Customer Code')
                    ->sortable()
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Customer code copied'),

                TextColumn::make('name')
                    ->label('Name')
                    ->sortable()
                    ->searchable()
                    ->weight('medium'),
                //                    ->description(fn ($record) => $record->isWalkin() ? 'Walk-in Customer' : null),

                TextColumn::make('phone')
                    ->label('Phone')
                    ->searchable()
                    ->sortable()
                    ->placeholder('No phone')
                    ->copyable()
                    ->copyMessage('Phone number copied')
                    ->formatStateUsing(fn ($state) => $state ?: 'Not provided'),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable()
                    ->placeholder('No email')
                    ->copyable()
                    ->copyMessage('Email copied')
                    ->formatStateUsing(fn ($state) => $state ?: 'Not provided'),

                TextColumn::make('invoices_count')
                    ->label('Invoices')
                    ->counts('invoices')
                    ->alignment('right')
                    ->sortable(),

                TextColumn::make('invoices_sum_total')
                    ->label('Total Invoice')
                    ->sum('invoices', 'total')
                    ->formatStateUsing(fn ($state) => Setting::formatMoney((int) round($state / 100)))
                    ->sortable()
                    ->alignment('right')
                    ->weight('semibold')
                    ->summarize([
                        Sum::make()
                            ->label('Total')
                            ->formatStateUsing(fn ($state) => Setting::formatMoney((int) round($state / 100))),
                    ]),

                TextColumn::make('invoices_sum_due')
                    ->label('Total Due')
                    ->sum('invoices', 'due')
                    ->formatStateUsing(fn ($state) => Setting::formatMoney((int) round($state / 100)))
                    ->sortable()
                    ->alignment('right')
                    ->color(fn ($state) => $state > 0 ? 'danger' : 'success')
                    ->summarize([
                        Sum::make()
                            ->label('Total')
                            ->formatStateUsing(fn ($state) => Setting::formatMoney((int) round($state / 100))),
                    ]),

                TextColumn::make('createdBy.name')
                    ->label('Created By')
                    ->sortable()
                    ->placeholder('System'),

                TextColumn::make('address')
                    ->label('Address')
                    ->searchable()
                    ->placeholder('No address')
                    ->limit(50)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();

                        return strlen($state) > 50 ? $state : null;
                    })
                    ->formatStateUsing(fn ($state) => $state ?: 'Not provided')
                    ->toggleable(isToggledHiddenByDefault: true),

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
                    ->description(fn ($record) => $record->updated_at->format('M j, Y g:i A'))
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('name')
                    ->label('Customer Name')
                    ->options(fn () => \App\Models\Customer::pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->query(fn ($query, $data) => $query->when($data['value'], fn ($query) => $query->where('id', $data['value']))),

                SelectFilter::make('created_by')
                    ->label('Created By')
                    ->relationship('createdBy', 'name')
                    ->searchable()
                    ->preload(),

                Filter::make('created_at')
                    ->label('Created Date')
                    ->schema([
                        DatePicker::make('created_from')
                            ->label('Created From'),
                        DatePicker::make('created_to')
                            ->label('Created To'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['created_from'], fn ($query) => $query->whereDate('created_at', '>=', $data['created_from']))
                            ->when($data['created_to'], fn ($query) => $query->whereDate('created_at', '<=', $data['created_to']));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['created_from']) {
                            $indicators[] = 'Created from: '.\Carbon\Carbon::parse($data['created_from'])->toFormattedDateString();
                        }
                        if ($data['created_to']) {
                            $indicators[] = 'Created to: '.\Carbon\Carbon::parse($data['created_to'])->toFormattedDateString();
                        }

                        return $indicators;
                    }),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('view')
                        ->label('View')
                        ->icon('heroicon-o-eye')
                        ->color('info')
                        ->modalHeading('Transactions')
                        ->modalWidth('5xl')
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Close')
                        ->modalContent(fn ($record) => view('filament.components.customer-transactions-wrapper', [
                            'customer' => $record,
                        ])),

                    EditAction::make()
                        ->slideOver()
                        ->modalWidth('md')
                        ->color('warning')
                        ->schema(\App\Filament\Resources\Customers\Schemas\CustomerForm::getFormComponents())
                        ->hidden(fn ($record) => $record->isWalkin()),

                    Action::make('duplicate')
                        ->label('Duplicate')
                        ->icon(Heroicon::OutlinedDocumentDuplicate)
                        ->color('gray')
                        ->schema(\App\Filament\Resources\Customers\Schemas\CustomerForm::getFormComponents())
                        ->fillForm(fn ($record) => [
                            'name' => $record->name.' (Copy)',
                            'phone' => $record->phone,
                            'email' => null, // Clear email to avoid duplicates
                            'address' => $record->address,
                        ])
                        ->action(function (array $data) {
                            \App\Models\Customer::create($data);
                        })
                        ->successNotificationTitle('Customer duplicated successfully')
                        ->hidden(fn ($record) => $record->isWalkin()),

                    DeleteAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('Delete Customer')
                        ->modalDescription('Are you sure you want to delete this customer? This action cannot be undone.')
                        ->hidden(fn ($record) => $record->isWalkin()),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('Delete Selected Customers')
                        ->modalDescription('Are you sure you want to delete the selected customers? This action cannot be undone.'),
                ]),
            ])
            ->deferFilters(false)
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->deferLoading()
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->persistSearchInSession();
    }
}
