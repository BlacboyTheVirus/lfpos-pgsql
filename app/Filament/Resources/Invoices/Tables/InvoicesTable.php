<?php

namespace App\Filament\Resources\Invoices\Tables;

use App\Enums\InvoiceStatus;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class InvoicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Invoice Code')
                    ->sortable()
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Invoice code copied'),

                TextColumn::make('customer.name')
                    ->label('Customer')
                    ->sortable()
                    ->searchable()
                    ->weight('medium'),


                TextColumn::make('date')
                    ->label('Invoice Date')
                    ->date('M j, Y')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn ($state) => $state?->getLabel() ?? $state)
                    ->badge()
                    ->color(fn ($state) => $state?->getColor() ?? 'gray')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('total')
                    ->label('Total')
                    ->formatStateUsing(fn ($state) => \App\Models\Setting::formatMoney((int) round($state * 1)))
                    ->sortable()
                    ->alignment('right')
                    ->weight('semibold'),

                TextColumn::make('paid')
                    ->label('Paid')
                    ->formatStateUsing(fn ($state) => \App\Models\Setting::formatMoney((int) round($state * 1)))
                    ->sortable()
                    ->alignment('right')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('due')
                    ->label('Due')
                    ->formatStateUsing(fn ($state) => \App\Models\Setting::formatMoney((int) round($state * 1)))
                    ->sortable()
                    ->alignment('right')
                    ->color(fn ($state) => $state > 0 ? 'danger' : 'success'),

                IconColumn::make('is_paid')
                    ->label('Paid')
                    ->boolean()
                    ->trueIcon(Heroicon::OutlinedCheckCircle)
                    ->falseIcon(Heroicon::OutlinedXCircle)
                    ->trueColor('success')
                    ->falseColor('danger'),

                TextColumn::make('createdBy.name')
                    ->label('Created By')
                    ->sortable()
                    ->placeholder('System')
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
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(InvoiceStatus::class)
                    ->searchable()
                    ->preload(),

                SelectFilter::make('customer_id')
                    ->label('Customer')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload(),

                Filter::make('date')
                    ->label('Invoice Date')
                    ->schema([
                        DatePicker::make('date_from')
                            ->label('From Date'),
                        DatePicker::make('date_to')
                            ->label('To Date'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['date_from'], fn ($query) => $query->whereDate('date', '>=', $data['date_from']))
                            ->when($data['date_to'], fn ($query) => $query->whereDate('date', '<=', $data['date_to']));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['date_from']) {
                            $indicators[] = 'From: '.\Carbon\Carbon::parse($data['date_from'])->toFormattedDateString();
                        }
                        if ($data['date_to']) {
                            $indicators[] = 'To: '.\Carbon\Carbon::parse($data['date_to'])->toFormattedDateString();
                        }

                        return $indicators;
                    }),

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
                    ViewAction::make()
                        ->slideOver()
                        ->modalWidth('md')
                        ->color('info')
                        ->infolist(\App\Filament\Resources\Invoices\Schemas\InvoiceInfolist::getInfolistComponents()),

                    EditAction::make()
                        ->slideOver()
                        ->modalWidth('md')
                        ->color('warning')
                        ->form(\App\Filament\Resources\Invoices\Schemas\InvoiceForm::getFormComponents()),

                    Action::make('duplicate')
                        ->label('Duplicate')
                        ->icon(Heroicon::OutlinedDocumentDuplicate)
                        ->color('gray')
                        ->form(\App\Filament\Resources\Invoices\Schemas\InvoiceForm::getFormComponents())
                        ->fillForm(fn ($record) => [
                            'customer_id' => $record->customer_id,
                            'date' => now()->toDateString(),
                            'subtotal' => $record->subtotal,
                            'discount' => $record->discount,
                            'round_off' => $record->round_off,
                            'total' => $record->total,
                            'status' => $record->status,
                            'note' => $record->note,
                        ])
                        ->action(function (array $data) {
                            \App\Models\Invoice::create($data);
                        })
                        ->successNotificationTitle('Invoice duplicated successfully'),

                    DeleteAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('Delete Invoice')
                        ->modalDescription('Are you sure you want to delete this invoice? This action cannot be undone.'),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('Delete Selected Invoices')
                        ->modalDescription('Are you sure you want to delete the selected invoices? This action cannot be undone.'),
                ]),
            ])
            ->defaultSort('date', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->deferLoading()
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->persistSearchInSession();
    }
}
