<?php

namespace App\Filament\Resources\Expenses\Tables;

use App\Enums\ExpenseCategory;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

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

                TextColumn::make('date')
                    ->label('Expense Date')
                    ->date('M j, Y')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('category')
                    ->label('Category')
                    ->formatStateUsing(fn ($state) => $state?->getLabel() ?? $state)
                    ->badge()
                    ->color(fn ($state) => $state?->getColor() ?? 'gray')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('amount')
                    ->label('Amount')
                    ->formatStateUsing(fn ($state) => \App\Models\Setting::formatMoney((int) round($state)))
                    ->sortable()
                    ->alignment('right')
                    ->summarize([
                        \Filament\Tables\Columns\Summarizers\Sum::make()
                            ->formatStateUsing(fn ($state) => \App\Models\Setting::formatMoney((int) round($state / 100))),
                    ]),

                TextColumn::make('description')
                    ->label('Description')
                    ->wrap()
                    ->lineClamp(2)
                    ->searchable()
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();

                        return strlen($state) > 50 ? $state : null;
                    }),

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
            ->groups([
                Group::make('date')
                    ->label('Expense Date')
                    ->date()
                    ->collapsible(),

                Group::make('category')
                    ->label('Category')
                    ->getTitleFromRecordUsing(fn ($record) => $record->category?->getLabel() ?? 'Uncategorized')
                    ->collapsible(),
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
            ->deferColumnManager(false)
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
                        ->schema(\App\Filament\Resources\Expenses\Schemas\ExpenseForm::getFormComponents()),

                    Action::make('duplicate')
                        ->label('Duplicate')
                        ->icon(Heroicon::OutlinedDocumentDuplicate)
                        ->color('gray')
                        ->schema(\App\Filament\Resources\Expenses\Schemas\ExpenseForm::getFormComponents())
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
            ->headerActions([
                ActionGroup::make([
                    Action::make('export-csv')
                        ->label('Export CSV')
                        ->icon(Heroicon::OutlinedDocumentText)
                        ->color('primary')
                        ->action(function (array $data) {
                            // Get filtered expenses
                            $expenses = \App\Models\Expense::query()
                                ->with(['createdBy'])
                                ->when($data['tableFilters']['category'] ?? null, function ($query, $category) {
                                    $query->where('category', $category);
                                })
                                ->when($data['tableFilters']['date'] ?? null, function ($query, $dateData) {
                                    if ($dateData['date_from'] ?? null) {
                                        $query->whereDate('date', '>=', $dateData['date_from']);
                                    }
                                    if ($dateData['date_to'] ?? null) {
                                        $query->whereDate('date', '<=', $dateData['date_to']);
                                    }
                                })
                                ->orderBy('date', 'desc')
                                ->get();

                            // Generate CSV
                            $filename = 'expenses-export-'.now()->format('Y-m-d_H-i-s').'.csv';

                            $headers = [
                                'Content-Type' => 'text/csv',
                                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
                            ];

                            $callback = function () use ($expenses) {
                                $file = fopen('php://output', 'w');

                                // Add CSV header
                                fputcsv($file, [
                                    'Expense Code',
                                    'Date',
                                    'Category',
                                    'Description',
                                    'Amount',
                                    'Notes',
                                    'Created By',
                                    'Created At',
                                ]);

                                // Add data rows
                                foreach ($expenses as $expense) {
                                    fputcsv($file, [
                                        $expense->code,
                                        $expense->date?->format('Y-m-d'),
                                        $expense->category?->getLabel() ?? '',
                                        $expense->description ?? '',
                                        number_format($expense->amount, 2, '.', ''),
                                        $expense->note ?? '',
                                        $expense->createdBy?->name ?? 'System',
                                        $expense->created_at?->format('Y-m-d H:i:s') ?? '',
                                    ]);
                                }

                                fclose($file);
                            };

                            return response()->stream($callback, 200, $headers);
                        }),

                    Action::make('export-excel')
                        ->label('Export Excel')
                        ->icon(Heroicon::OutlinedTableCells)
                        ->color('success')
                        ->action(function (array $data) {
                            // Get filtered expenses
                            $expenses = \App\Models\Expense::query()
                                ->with(['createdBy'])
                                ->when($data['tableFilters']['category'] ?? null, function ($query, $category) {
                                    $query->where('category', $category);
                                })
                                ->when($data['tableFilters']['date'] ?? null, function ($query, $dateData) {
                                    if ($dateData['date_from'] ?? null) {
                                        $query->whereDate('date', '>=', $dateData['date_from']);
                                    }
                                    if ($dateData['date_to'] ?? null) {
                                        $query->whereDate('date', '<=', $dateData['date_to']);
                                    }
                                })
                                ->orderBy('date', 'desc')
                                ->get();

                            // Generate Excel file using OpenSpout
                            $filename = 'expenses-export-'.now()->format('Y-m-d_H-i-s').'.xlsx';

                            $headers = [
                                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
                            ];

                            $callback = function () use ($expenses) {
                                $writer = new \OpenSpout\Writer\XLSX\Writer;
                                $writer->openToFile('php://output');

                                // Add header row
                                $headerRow = \OpenSpout\Common\Entity\Row::fromValues([
                                    'Expense Code',
                                    'Date',
                                    'Category',
                                    'Description',
                                    'Amount',
                                    'Notes',
                                    'Created By',
                                    'Created At',
                                ]);
                                $writer->addRow($headerRow);

                                // Add data rows
                                foreach ($expenses as $expense) {
                                    $row = \OpenSpout\Common\Entity\Row::fromValues([
                                        $expense->code,
                                        $expense->date?->format('Y-m-d'),
                                        $expense->category?->getLabel() ?? '',
                                        $expense->description ?? '',
                                        (float) $expense->amount,
                                        $expense->note ?? '',
                                        $expense->createdBy?->name ?? 'System',
                                        $expense->created_at?->format('Y-m-d H:i:s') ?? '',
                                    ]);
                                    $writer->addRow($row);
                                }

                                $writer->close();
                            };

                            return response()->stream($callback, 200, $headers);
                        }),

                    Action::make('export-pdf')
                        ->label('Export PDF')
                        ->icon(Heroicon::OutlinedDocumentArrowDown)
                        ->color('info')
                        ->action(function (array $data) {
                            // Get filtered expenses
                            $expenses = \App\Models\Expense::query()
                                ->with(['createdBy'])
                                ->when($data['tableFilters']['category'] ?? null, function ($query, $category) {
                                    $query->where('category', $category);
                                })
                                ->when($data['tableFilters']['date'] ?? null, function ($query, $dateData) {
                                    if ($dateData['date_from'] ?? null) {
                                        $query->whereDate('date', '>=', $dateData['date_from']);
                                    }
                                    if ($dateData['date_to'] ?? null) {
                                        $query->whereDate('date', '<=', $dateData['date_to']);
                                    }
                                })
                                ->orderBy('date', 'desc')
                                ->get();

                            // Generate PDF
                            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.expenses-pdf', [
                                'expenses' => $expenses,
                                'filters' => $data['tableFilters'] ?? [],
                                'currentPage' => 1,
                                'totalPages' => 1,
                            ]);

                            $filename = 'expenses-export-'.now()->format('Y-m-d_H-i-s').'.pdf';

                            return response()->streamDownload(function () use ($pdf) {
                                echo $pdf->output();
                            }, $filename, [
                                'Content-Type' => 'application/pdf',
                                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
                            ]);
                        }),
                ])
                    ->label('Export')
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->color('primary')
                    ->dropdown(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('delete')
                        ->label('Delete Selected')
                        ->icon(Heroicon::OutlinedTrash)
                        ->color('danger')
                        ->modalHeading('Delete Expenses')
                        ->modalDescription('This action will permanently delete the selected expenses. This cannot be undone.')
                        ->modalSubmitActionLabel('Delete Expenses')
                        ->modalWidth('sm')
                        ->modalAlignment('center')
                        ->form([
                            TextInput::make('confirmation')
                                ->label('Type "DELETE" to confirm')
                                ->placeholder('DELETE')
                                ->required()
                                ->rules(['in:DELETE'])
                                ->validationMessages([
                                    'in' => 'You must type "DELETE" exactly to confirm deletion.',
                                ])
                                ->helperText('This action cannot be undone. Type "DELETE" to confirm.')
                                ->autofocus(),
                        ])
                        ->action(function (Collection $records, array $data) {
                            if ($data['confirmation'] !== 'DELETE') {
                                return;
                            }
                            $records->each(function ($record) {
                                $record->delete();
                            });
                            \Filament\Notifications\Notification::make()
                                ->title('Expenses Deleted')
                                ->body(count($records).' expense(s) have been deleted successfully.')
                                ->success()
                                ->send();
                        }),

                    Action::make('export-selected-csv')
                        ->label('Export Selected CSV')
                        ->icon(Heroicon::OutlinedDocumentText)
                        ->color('primary')
                        ->deselectRecordsAfterCompletion()
                        ->action(function (array $data) {
                            // Get selected records
                            $records = collect($data['selectedTableRecords']);
                            $expenseIds = $records->map(fn ($record) => $record['id'])->toArray();

                            $expenses = \App\Models\Expense::whereIn('id', $expenseIds)
                                ->with(['createdBy'])
                                ->orderBy('date', 'desc')
                                ->get();

                            // Generate CSV
                            $filename = 'selected-expenses-'.now()->format('Y-m-d_H-i-s').'.csv';

                            $headers = [
                                'Content-Type' => 'text/csv',
                                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
                            ];

                            $callback = function () use ($expenses) {
                                $file = fopen('php://output', 'w');

                                // Add CSV header
                                fputcsv($file, [
                                    'Expense Code',
                                    'Date',
                                    'Category',
                                    'Description',
                                    'Amount',
                                    'Notes',
                                    'Created By',
                                    'Created At',
                                ]);

                                // Add data rows
                                foreach ($expenses as $expense) {
                                    fputcsv($file, [
                                        $expense->code,
                                        $expense->date?->format('Y-m-d'),
                                        $expense->category?->getLabel() ?? '',
                                        $expense->description ?? '',
                                        number_format($expense->amount, 2, '.', ''),
                                        $expense->note ?? '',
                                        $expense->createdBy?->name ?? 'System',
                                        $expense->created_at?->format('Y-m-d H:i:s') ?? '',
                                    ]);
                                }

                                fclose($file);
                            };

                            return response()->stream($callback, 200, $headers);
                        }),

                    Action::make('export-selected-excel')
                        ->label('Export Selected Excel')
                        ->icon(Heroicon::OutlinedTableCells)
                        ->color('success')
                        ->deselectRecordsAfterCompletion()
                        ->action(function (array $data) {
                            // Get selected records
                            $records = collect($data['selectedTableRecords']);
                            $expenseIds = $records->map(fn ($record) => $record['id'])->toArray();

                            $expenses = \App\Models\Expense::whereIn('id', $expenseIds)
                                ->with(['createdBy'])
                                ->orderBy('date', 'desc')
                                ->get();

                            // Generate Excel file using OpenSpout
                            $filename = 'selected-expenses-'.now()->format('Y-m-d_H-i-s').'.xlsx';

                            $headers = [
                                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
                            ];

                            $callback = function () use ($expenses) {
                                $writer = new \OpenSpout\Writer\XLSX\Writer;
                                $writer->openToFile('php://output');

                                // Add header row
                                $headerRow = \OpenSpout\Common\Entity\Row::fromValues([
                                    'Expense Code',
                                    'Date',
                                    'Category',
                                    'Description',
                                    'Amount',
                                    'Notes',
                                    'Created By',
                                    'Created At',
                                ]);
                                $writer->addRow($headerRow);

                                // Add data rows
                                foreach ($expenses as $expense) {
                                    $row = \OpenSpout\Common\Entity\Row::fromValues([
                                        $expense->code,
                                        $expense->date?->format('Y-m-d'),
                                        $expense->category?->getLabel() ?? '',
                                        $expense->description ?? '',
                                        (float) $expense->amount,
                                        $expense->note ?? '',
                                        $expense->createdBy?->name ?? 'System',
                                        $expense->created_at?->format('Y-m-d H:i:s') ?? '',
                                    ]);
                                    $writer->addRow($row);
                                }

                                $writer->close();
                            };

                            return response()->stream($callback, 200, $headers);
                        }),

                    Action::make('export-selected-pdf')
                        ->label('Export Selected PDF')
                        ->icon(Heroicon::OutlinedDocumentArrowDown)
                        ->color('info')
                        ->deselectRecordsAfterCompletion()
                        ->action(function (array $data) {
                            // Get selected records
                            $records = collect($data['selectedTableRecords']);
                            $expenseIds = $records->map(fn ($record) => $record['id'])->toArray();

                            $expenses = \App\Models\Expense::whereIn('id', $expenseIds)
                                ->with(['createdBy'])
                                ->orderBy('date', 'desc')
                                ->get();

                            // Generate PDF
                            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.expenses-pdf', [
                                'expenses' => $expenses,
                                'filters' => $data['tableFilters'] ?? [],
                                'currentPage' => 1,
                                'totalPages' => 1,
                            ]);

                            $filename = 'selected-expenses-'.now()->format('Y-m-d_H-i-s').'.pdf';

                            return response()->streamDownload(function () use ($pdf) {
                                echo $pdf->output();
                            }, $filename, [
                                'Content-Type' => 'application/pdf',
                                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
                            ]);
                        }),
                ])
                    ->label('Bulk Actions')
                    ->icon(Heroicon::OutlinedEllipsisVertical)
                    ->color('gray')
                    ->dropdown(),
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
