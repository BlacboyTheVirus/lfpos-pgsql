<?php

namespace App\Filament\Resources\Customers\Tables;

use App\Models\Customer;
use App\Models\Setting;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\UniqueConstraintViolationException;

class CustomersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function ($query) {
                return $query
                    ->with(['createdBy'])
                    ->withCount('invoices')
                    ->withSum('invoices', 'total')
                    ->withSum('invoices', 'due');
            })
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
                    ->alignment('right')
                    ->sortable(),

                TextColumn::make('invoices_sum_total')
                    ->label('Total Invoice')
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
                    ->formatStateUsing(fn ($state) => Setting::formatMoney((int) round($state / 100)))
                    ->sortable()
                    ->alignment('right')
                    ->color(fn ($state) => $state > 0 ? 'danger' : '')
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
                    ->searchable()
                    ->preload(false)  // Don't preload all customers
                    ->getSearchResultsUsing(fn (string $search) =>
                        \App\Models\Customer::where('name', 'like', "%{$search}%")
                            ->limit(50)
                            ->pluck('name', 'id')
                    )
                    ->getOptionLabelUsing(fn ($value) =>
                        \App\Models\Customer::find($value)?->name
                    )
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
            ->headerActions([
                ActionGroup::make([
                    Action::make('export-csv')
                        ->label('Export CSV')
                        ->icon(Heroicon::OutlinedDocumentText)
                        ->color('primary')
                        ->action(function (array $data) {
                            // Get filtered customers
                            $customers = \App\Models\Customer::query()
                                ->with(['createdBy'])
                                ->withCount('invoices')
                                ->withSum('invoices', 'total')
                                ->withSum('invoices', 'due')
                                ->when($data['tableFilters']['name'] ?? null, function ($query, $customerId) {
                                    $query->where('id', $customerId);
                                })
                                ->when($data['tableFilters']['created_by'] ?? null, function ($query, $createdBy) {
                                    $query->where('created_by', $createdBy);
                                })
                                ->when($data['tableFilters']['created_at'] ?? null, function ($query, $dateData) {
                                    if ($dateData['created_from'] ?? null) {
                                        $query->whereDate('created_at', '>=', $dateData['created_from']);
                                    }
                                    if ($dateData['created_to'] ?? null) {
                                        $query->whereDate('created_at', '<=', $dateData['created_to']);
                                    }
                                })
                                ->orderBy('created_at', 'desc')
                                ->get();

                            // Generate CSV
                            $filename = 'customers-export-'.now()->format('Y-m-d_H-i-s').'.csv';

                            $headers = [
                                'Content-Type' => 'text/csv',
                                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
                            ];

                            $callback = function () use ($customers) {
                                $file = fopen('php://output', 'w');

                                // Add CSV header
                                fputcsv($file, [
                                    'Customer Code',
                                    'Name',
                                    'Phone',
                                    'Email',
                                    'Address',
                                    'Total Invoices',
                                    'Total Invoice Amount',
                                    'Total Due',
                                    'Created By',
                                    'Created At',
                                ]);

                                // Add data rows
                                foreach ($customers as $customer) {
                                    fputcsv($file, [
                                        $customer->code,
                                        $customer->name,
                                        $customer->phone ?? '',
                                        $customer->email ?? '',
                                        $customer->address ?? '',
                                        $customer->invoices_count ?? 0,
                                        number_format(($customer->invoices_sum_total ?? 0) / 100, 2, '.', ''),
                                        number_format(($customer->invoices_sum_due ?? 0) / 100, 2, '.', ''),
                                        $customer->createdBy?->name ?? 'System',
                                        $customer->created_at?->format('Y-m-d H:i:s') ?? '',
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
                            // Get filtered customers
                            $customers = \App\Models\Customer::query()
                                ->with(['createdBy'])
                                ->withCount('invoices')
                                ->withSum('invoices', 'total')
                                ->withSum('invoices', 'due')
                                ->when($data['tableFilters']['name'] ?? null, function ($query, $customerId) {
                                    $query->where('id', $customerId);
                                })
                                ->when($data['tableFilters']['created_by'] ?? null, function ($query, $createdBy) {
                                    $query->where('created_by', $createdBy);
                                })
                                ->when($data['tableFilters']['created_at'] ?? null, function ($query, $dateData) {
                                    if ($dateData['created_from'] ?? null) {
                                        $query->whereDate('created_at', '>=', $dateData['created_from']);
                                    }
                                    if ($dateData['created_to'] ?? null) {
                                        $query->whereDate('created_at', '<=', $dateData['created_to']);
                                    }
                                })
                                ->orderBy('created_at', 'desc')
                                ->get();

                            // Generate Excel file using OpenSpout
                            $filename = 'customers-export-'.now()->format('Y-m-d_H-i-s').'.xlsx';

                            $headers = [
                                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
                            ];

                            $callback = function () use ($customers) {
                                $writer = new \OpenSpout\Writer\XLSX\Writer;
                                $writer->openToFile('php://output');

                                // Add header row
                                $headerRow = \OpenSpout\Common\Entity\Row::fromValues([
                                    'Customer Code',
                                    'Name',
                                    'Phone',
                                    'Email',
                                    'Address',
                                    'Total Invoices',
                                    'Total Invoice Amount',
                                    'Total Due',
                                    'Created By',
                                    'Created At',
                                ]);
                                $writer->addRow($headerRow);

                                // Add data rows
                                foreach ($customers as $customer) {
                                    $row = \OpenSpout\Common\Entity\Row::fromValues([
                                        $customer->code,
                                        $customer->name,
                                        $customer->phone ?? '',
                                        $customer->email ?? '',
                                        $customer->address ?? '',
                                        $customer->invoices_count ?? 0,
                                        (float) (($customer->invoices_sum_total ?? 0) / 100),
                                        (float) (($customer->invoices_sum_due ?? 0) / 100),
                                        $customer->createdBy?->name ?? 'System',
                                        $customer->created_at?->format('Y-m-d H:i:s') ?? '',
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
                            // Get filtered customers
                            $customers = \App\Models\Customer::query()
                                ->with(['createdBy'])
                                ->withCount('invoices')
                                ->withSum('invoices', 'total')
                                ->withSum('invoices', 'due')
                                ->when($data['tableFilters']['name'] ?? null, function ($query, $customerId) {
                                    $query->where('id', $customerId);
                                })
                                ->when($data['tableFilters']['created_by'] ?? null, function ($query, $createdBy) {
                                    $query->where('created_by', $createdBy);
                                })
                                ->when($data['tableFilters']['created_at'] ?? null, function ($query, $dateData) {
                                    if ($dateData['created_from'] ?? null) {
                                        $query->whereDate('created_at', '>=', $dateData['created_from']);
                                    }
                                    if ($dateData['created_to'] ?? null) {
                                        $query->whereDate('created_at', '<=', $dateData['created_to']);
                                    }
                                })
                                ->orderBy('created_at', 'desc')
                                ->get();

                            // Generate PDF
                            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.customers-pdf', [
                                'customers' => $customers,
                                'filters' => $data['tableFilters'] ?? [],
                                'currentPage' => 1,
                                'totalPages' => 1,
                            ]);

                            $filename = 'customers-export-'.now()->format('Y-m-d_H-i-s').'.pdf';

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
                        ->using(function ($record, array $data, EditAction $action): Customer {
                            try {
                                $record->update($data);

                                return $record;
                            } catch (UniqueConstraintViolationException $e) {
                                // Send error notification
                                Notification::make()
                                    ->title('Customer name already exists')
                                    ->body('A customer with this name already exists (case-insensitive match). Please use a different name.')
                                    ->danger()
                                    ->send();

                                // Halt the action to keep the modal open
                                $action->halt();
                            }
                        })
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
                        ->action(function (array $data, Action $action) {
                            try {
                                Customer::create($data);
                            } catch (UniqueConstraintViolationException $e) {
                                // Send error notification
                                Notification::make()
                                    ->title('Customer already exists')
                                    ->body('A customer with this name already exists (case-insensitive match). Please use a different name.')
                                    ->danger()
                                    ->send();

                                // Halt the action to keep the modal open
                                $action->halt();
                            }
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
                    BulkAction::make('delete')
                        ->label('Delete Selected')
                        ->icon(Heroicon::OutlinedTrash)
                        ->color('danger')
                        ->modalHeading('Delete Customers')
                        ->modalDescription('This action will permanently delete the selected customers. This cannot be undone.')
                        ->modalSubmitActionLabel('Delete Customers')
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
                                ->title('Customers Deleted')
                                ->body(count($records).' customer(s) have been deleted successfully.')
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
                            $customerIds = $records->map(fn ($record) => $record['id'])->toArray();

                            $customers = \App\Models\Customer::whereIn('id', $customerIds)
                                ->with(['createdBy'])
                                ->withCount('invoices')
                                ->withSum('invoices', 'total')
                                ->withSum('invoices', 'due')
                                ->orderBy('created_at', 'desc')
                                ->get();

                            // Generate CSV
                            $filename = 'selected-customers-'.now()->format('Y-m-d_H-i-s').'.csv';

                            $headers = [
                                'Content-Type' => 'text/csv',
                                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
                            ];

                            $callback = function () use ($customers) {
                                $file = fopen('php://output', 'w');

                                // Add CSV header
                                fputcsv($file, [
                                    'Customer Code',
                                    'Name',
                                    'Phone',
                                    'Email',
                                    'Address',
                                    'Total Invoices',
                                    'Total Invoice Amount',
                                    'Total Due',
                                    'Created By',
                                    'Created At',
                                ]);

                                // Add data rows
                                foreach ($customers as $customer) {
                                    fputcsv($file, [
                                        $customer->code,
                                        $customer->name,
                                        $customer->phone ?? '',
                                        $customer->email ?? '',
                                        $customer->address ?? '',
                                        $customer->invoices_count ?? 0,
                                        number_format(($customer->invoices_sum_total ?? 0) / 100, 2, '.', ''),
                                        number_format(($customer->invoices_sum_due ?? 0) / 100, 2, '.', ''),
                                        $customer->createdBy?->name ?? 'System',
                                        $customer->created_at?->format('Y-m-d H:i:s') ?? '',
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
                            $customerIds = $records->map(fn ($record) => $record['id'])->toArray();

                            $customers = \App\Models\Customer::whereIn('id', $customerIds)
                                ->with(['createdBy'])
                                ->withCount('invoices')
                                ->withSum('invoices', 'total')
                                ->withSum('invoices', 'due')
                                ->orderBy('created_at', 'desc')
                                ->get();

                            // Generate Excel file using OpenSpout
                            $filename = 'selected-customers-'.now()->format('Y-m-d_H-i-s').'.xlsx';

                            $headers = [
                                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
                            ];

                            $callback = function () use ($customers) {
                                $writer = new \OpenSpout\Writer\XLSX\Writer;
                                $writer->openToFile('php://output');

                                // Add header row
                                $headerRow = \OpenSpout\Common\Entity\Row::fromValues([
                                    'Customer Code',
                                    'Name',
                                    'Phone',
                                    'Email',
                                    'Address',
                                    'Total Invoices',
                                    'Total Invoice Amount',
                                    'Total Due',
                                    'Created By',
                                    'Created At',
                                ]);
                                $writer->addRow($headerRow);

                                // Add data rows
                                foreach ($customers as $customer) {
                                    $row = \OpenSpout\Common\Entity\Row::fromValues([
                                        $customer->code,
                                        $customer->name,
                                        $customer->phone ?? '',
                                        $customer->email ?? '',
                                        $customer->address ?? '',
                                        $customer->invoices_count ?? 0,
                                        (float) (($customer->invoices_sum_total ?? 0) / 100),
                                        (float) (($customer->invoices_sum_due ?? 0) / 100),
                                        $customer->createdBy?->name ?? 'System',
                                        $customer->created_at?->format('Y-m-d H:i:s') ?? '',
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
                            $customerIds = $records->map(fn ($record) => $record['id'])->toArray();

                            $customers = \App\Models\Customer::whereIn('id', $customerIds)
                                ->with(['createdBy'])
                                ->withCount('invoices')
                                ->withSum('invoices', 'total')
                                ->withSum('invoices', 'due')
                                ->orderBy('created_at', 'desc')
                                ->get();

                            // Generate PDF
                            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.customers-pdf', [
                                'customers' => $customers,
                                'filters' => $data['tableFilters'] ?? [],
                                'currentPage' => 1,
                                'totalPages' => 1,
                            ]);

                            $filename = 'selected-customers-'.now()->format('Y-m-d_H-i-s').'.pdf';

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
            ->deferFilters(false)
            ->deferColumnManager(false)
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->deferLoading()
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->persistSearchInSession();
    }
}
