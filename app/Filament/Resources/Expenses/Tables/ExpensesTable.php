<?php

namespace App\Filament\Resources\Expenses\Tables;

use App\Enums\ExpenseCategory;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ExpensesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Expense Code')
                    ->sortable()
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Expense code copied'),

                TextColumn::make('category')
                    ->label('Category')
                    ->formatStateUsing(fn ($state) => $state?->getLabel() ?? $state)
                    ->badge()
                    ->color(fn ($state) => $state?->getColor() ?? 'gray')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('date')
                    ->label('Expense Date')
                    ->date('M j, Y')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('description')
                    ->label('Description')
                    ->wrap()
                    ->lineClamp(2)
                    ->searchable()
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();

                        return strlen($state) > 50 ? $state : null;
                    }),

                TextColumn::make('amount')
                    ->label('Amount')
                    ->formatStateUsing(fn ($state) => \App\Models\Setting::formatMoney((int) round($state)))
                    ->sortable()
                    ->alignment('right')
                    ->summarize([
                        \Filament\Tables\Columns\Summarizers\Sum::make()
                            ->formatStateUsing(fn ($state) => \App\Models\Setting::formatMoney((int) round($state / 100))),
                    ]),

                TextColumn::make('note')
                    ->label('Note')
                    ->limit(30)
                    ->placeholder('No note')
                    ->toggleable(isToggledHiddenByDefault: true),

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
                SelectFilter::make('category')
                    ->label('Category')
                    ->options(ExpenseCategory::class)
                    ->searchable()
                    ->preload(),

                Filter::make('date')
                    ->label('Expense Date')
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

//                SelectFilter::make('created_by')
//                    ->label('Created By')
//                    ->relationship('createdBy', 'name')
//                    ->searchable()
//                    ->preload(),


            ])
            ->deferFilters(false)
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->slideOver()
                        ->modalWidth('md')
                        ->color('info')
                        ->infolist(\App\Filament\Resources\Expenses\Schemas\ExpenseInfolist::getInfolistComponents()),

                    EditAction::make()
                        ->slideOver()
                        ->modalWidth('md')
                        ->color('warning')
                        ->form(\App\Filament\Resources\Expenses\Schemas\ExpenseForm::getFormComponents()),

                    Action::make('duplicate')
                        ->label('Duplicate')
                        ->icon(Heroicon::OutlinedDocumentDuplicate)
                        ->color('gray')
                        ->form(\App\Filament\Resources\Expenses\Schemas\ExpenseForm::getFormComponents())
                        ->fillForm(fn ($record) => [
                            'category' => $record->category,
                            'description' => $record->description.' (Copy)',
                            'amount' => $record->amount,
                            'note' => $record->note,
                            'date' => now()->toDateString(),
                        ])
                        ->action(function (array $data) {
                            \App\Models\Expense::create($data);
                        })
                        ->successNotificationTitle('Expense duplicated successfully'),

                    DeleteAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('Delete Expense')
                        ->modalDescription('Are you sure you want to delete this expense? This action cannot be undone.'),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('Delete Selected Expenses')
                        ->modalDescription('Are you sure you want to delete the selected expenses? This action cannot be undone.'),
                ]),
            ])
            ->defaultSort('date', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 1])
            ->deferLoading()
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->persistSearchInSession();
    }
}
