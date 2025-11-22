<?php

namespace App\Filament\Resources\Invoices\Tables;

use App\Enums\InvoiceStatus;
use App\Filament\Resources\Invoices\InvoiceResource;
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
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

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

                TextColumn::make('total')
                    ->label('Total')
                    ->formatStateUsing(fn ($state) => \App\Models\Setting::formatMoney((int) round($state * 1)))
                    ->sortable()
                    ->alignment('right')
                    ->weight('semibold')
                    ->summarize([
                        Sum::make()
                            ->label('Total Invoices')
                            ->formatStateUsing(fn ($state) => \App\Models\Setting::formatMoney((int) round($state / 100))),
                    ]),

                TextColumn::make('paid')
                    ->label('Paid')
                    ->formatStateUsing(fn ($state) => \App\Models\Setting::formatMoney((int) round($state * 1)))
                    ->sortable()
                    ->alignment('right')
                    ->summarize([
                        Sum::make()
                            ->label('Total Payments')
                            ->formatStateUsing(fn ($state) => \App\Models\Setting::formatMoney((int) round($state / 100))),
                    ]),

                TextColumn::make('due')
                    ->label('Due')
                    ->formatStateUsing(fn ($state) => \App\Models\Setting::formatMoney((int) round($state * 1)))
                    ->sortable()
                    ->alignment('right')
                    ->color(fn ($state) => $state > 0 ? 'danger' : 'success')
                    ->summarize([
                        Sum::make()
                            ->label('Total Due')
                            ->formatStateUsing(fn ($state) => \App\Models\Setting::formatMoney((int) round($state / 100))),
                    ]),

                TextColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn ($state) => $state?->getLabel() ?? $state)
                    ->badge()
                    ->color(fn ($state) => $state?->getColor() ?? 'gray')
                    ->searchable()
                    ->sortable(),

                //                IconColumn::make('is_paid')
                //                    ->label('Paid')
                //                    ->boolean()
                //                    ->trueIcon(Heroicon::OutlinedCheckCircle)
                //                    ->falseIcon(Heroicon::OutlinedXCircle)
                //                    ->trueColor('success')
                //                    ->falseColor('danger'),

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
                    ->label('Invoice Date')
                    ->date()
                    ->collapsible(),

                Group::make('customer.name')
                    ->label('Customer')
                    ->collapsible(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'outstanding' => 'Outstanding',
                        'partial' => 'Partial',
                        'unpaid' => 'Unpaid',
                        'paid' => 'Paid',
                    ])
                    ->query(function ($query, array $data) {
                        if (! isset($data['value']) || $data['value'] === 'all' || $data['value'] === null) {
                            return $query;
                        }

                        if ($data['value'] === 'outstanding') {
                            return $query->whereIn('status', [InvoiceStatus::Unpaid, InvoiceStatus::Partial]);
                        }

                        if ($data['value'] === 'partial') {
                            return $query->where('status', InvoiceStatus::Partial);
                        }

                        if ($data['value'] === 'unpaid') {
                            return $query->where('status', InvoiceStatus::Unpaid);
                        }

                        if ($data['value'] === 'paid') {
                            return $query->where('status', InvoiceStatus::Paid);
                        }

                        return $query;
                    })
                    ->default('all'),

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
            ])
            ->headerActions([
                ActionGroup::make([
                    Action::make('export-csv')
                        ->label('Export CSV')
                        ->icon(Heroicon::OutlinedDocumentText)
                        ->color('primary')
                        ->action(function (array $data) {
                            // Get filtered invoices
                            $invoices = \App\Models\Invoice::query()
                                ->with(['customer', 'createdBy'])
                                ->when($data['tableFilters']['status'] ?? null, function ($query, $status) {
                                    if ($status === 'outstanding') {
                                        return $query->whereIn('status', [\App\Enums\InvoiceStatus::Unpaid, \App\Enums\InvoiceStatus::Partial]);
                                    }
                                    if ($status === 'partial') {
                                        return $query->where('status', \App\Enums\InvoiceStatus::Partial);
                                    }
                                    if ($status === 'unpaid') {
                                        return $query->where('status', \App\Enums\InvoiceStatus::Unpaid);
                                    }
                                    if ($status === 'paid') {
                                        return $query->where('status', \App\Enums\InvoiceStatus::Paid);
                                    }
                                })
                                ->when($data['tableFilters']['customer_id'] ?? null, function ($query, $customerId) {
                                    $query->where('customer_id', $customerId);
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
                            $filename = 'invoices-export-'.now()->format('Y-m-d_H-i-s').'.csv';

                            $headers = [
                                'Content-Type' => 'text/csv',
                                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
                            ];

                            $callback = function () use ($invoices) {
                                $file = fopen('php://output', 'w');

                                // Add CSV header
                                fputcsv($file, [
                                    'Invoice Code',
                                    'Customer',
                                    'Date',
                                    'Total',
                                    'Paid',
                                    'Due',
                                    'Status',
                                    'Created By',
                                    'Created At',
                                ]);

                                // Add data rows
                                foreach ($invoices as $invoice) {
                                    fputcsv($file, [
                                        $invoice->code,
                                        $invoice->customer?->name ?? '',
                                        $invoice->date?->format('Y-m-d'),
                                        number_format($invoice->total, 2, '.', ''),
                                        number_format($invoice->paid, 2, '.', ''),
                                        number_format($invoice->due, 2, '.', ''),
                                        $invoice->status?->getLabel() ?? '',
                                        $invoice->createdBy?->name ?? 'System',
                                        $invoice->created_at?->format('Y-m-d H:i:s') ?? '',
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
                            // Get filtered invoices
                            $invoices = \App\Models\Invoice::query()
                                ->with(['customer', 'createdBy'])
                                ->when($data['tableFilters']['status'] ?? null, function ($query, $status) {
                                    if ($status === 'outstanding') {
                                        return $query->whereIn('status', [\App\Enums\InvoiceStatus::Unpaid, \App\Enums\InvoiceStatus::Partial]);
                                    }
                                    if ($status === 'partial') {
                                        return $query->where('status', \App\Enums\InvoiceStatus::Partial);
                                    }
                                    if ($status === 'unpaid') {
                                        return $query->where('status', \App\Enums\InvoiceStatus::Unpaid);
                                    }
                                    if ($status === 'paid') {
                                        return $query->where('status', \App\Enums\InvoiceStatus::Paid);
                                    }
                                })
                                ->when($data['tableFilters']['customer_id'] ?? null, function ($query, $customerId) {
                                    $query->where('customer_id', $customerId);
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
                            $filename = 'invoices-export-'.now()->format('Y-m-d_H-i-s').'.xlsx';

                            $headers = [
                                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
                            ];

                            $callback = function () use ($invoices) {
                                $writer = new \OpenSpout\Writer\XLSX\Writer;
                                $writer->openToFile('php://output');

                                // Add header row
                                $headerRow = \OpenSpout\Common\Entity\Row::fromValues([
                                    'Invoice Code',
                                    'Customer',
                                    'Date',
                                    'Total',
                                    'Paid',
                                    'Due',
                                    'Status',
                                    'Created By',
                                    'Created At',
                                ]);
                                $writer->addRow($headerRow);

                                // Add data rows
                                foreach ($invoices as $invoice) {
                                    $row = \OpenSpout\Common\Entity\Row::fromValues([
                                        $invoice->code,
                                        $invoice->customer?->name ?? '',
                                        $invoice->date?->format('Y-m-d'),
                                        (float) $invoice->total,
                                        (float) $invoice->paid,
                                        (float) $invoice->due,
                                        $invoice->status?->getLabel() ?? '',
                                        $invoice->createdBy?->name ?? 'System',
                                        $invoice->created_at?->format('Y-m-d H:i:s') ?? '',
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
                            // Get filtered invoices
                            $invoices = \App\Models\Invoice::query()
                                ->with(['customer', 'createdBy'])
                                ->when($data['tableFilters']['status'] ?? null, function ($query, $status) {
                                    if ($status === 'outstanding') {
                                        return $query->whereIn('status', [\App\Enums\InvoiceStatus::Unpaid, \App\Enums\InvoiceStatus::Partial]);
                                    }
                                    if ($status === 'partial') {
                                        return $query->where('status', \App\Enums\InvoiceStatus::Partial);
                                    }
                                    if ($status === 'unpaid') {
                                        return $query->where('status', \App\Enums\InvoiceStatus::Unpaid);
                                    }
                                    if ($status === 'paid') {
                                        return $query->where('status', \App\Enums\InvoiceStatus::Paid);
                                    }
                                })
                                ->when($data['tableFilters']['customer_id'] ?? null, function ($query, $customerId) {
                                    $query->where('customer_id', $customerId);
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
                            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.invoices-pdf', [
                                'invoices' => $invoices,
                                'filters' => $data['tableFilters'] ?? [],
                                'currentPage' => 1,
                                'totalPages' => 1,
                            ]);

                            $filename = 'invoices-export-'.now()->format('Y-m-d_H-i-s').'.pdf';

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
                    ViewAction::make()
                        ->url(fn ($record) => InvoiceResource::getUrl('view', ['record' => $record]))
                        ->color('info'),

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
                    BulkAction::make('delete')
                        ->label('Delete Selected')
                        ->icon(Heroicon::OutlinedTrash)
                        ->color('danger')
                        ->modalHeading('Delete Invoices')
                        ->modalDescription('This action will permanently delete the selected invoices. This cannot be undone.')
                        ->modalSubmitActionLabel('Delete Invoices')
                        ->modalWidth('sm')
                        ->modalAlignment('center')
                        ->form([
                            TextInput::make('confirmation')
                                ->label('Type "DELETE" to confirm')
                                ->placeholder('DELETE')
                                ->required()
                                ->autocomplete(false)
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
                                ->title('Invoices Deleted')
                                ->body(count($records).' invoice(s) have been deleted successfully.')
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
                            $invoiceIds = $records->map(fn ($record) => $record['id'])->toArray();

                            $invoices = \App\Models\Invoice::whereIn('id', $invoiceIds)
                                ->with(['customer', 'createdBy'])
                                ->orderBy('date', 'desc')
                                ->get();

                            // Generate CSV
                            $filename = 'selected-invoices-'.now()->format('Y-m-d_H-i-s').'.csv';

                            $headers = [
                                'Content-Type' => 'text/csv',
                                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
                            ];

                            $callback = function () use ($invoices) {
                                $file = fopen('php://output', 'w');

                                // Add CSV header
                                fputcsv($file, [
                                    'Invoice Code',
                                    'Customer',
                                    'Date',
                                    'Total',
                                    'Paid',
                                    'Due',
                                    'Status',
                                    'Created By',
                                    'Created At',
                                ]);

                                // Add data rows
                                foreach ($invoices as $invoice) {
                                    fputcsv($file, [
                                        $invoice->code,
                                        $invoice->customer?->name ?? '',
                                        $invoice->date?->format('Y-m-d'),
                                        number_format($invoice->total, 2, '.', ''),
                                        number_format($invoice->paid, 2, '.', ''),
                                        number_format($invoice->due, 2, '.', ''),
                                        $invoice->status?->getLabel() ?? '',
                                        $invoice->createdBy?->name ?? 'System',
                                        $invoice->created_at?->format('Y-m-d H:i:s') ?? '',
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
                            $invoiceIds = $records->map(fn ($record) => $record['id'])->toArray();

                            $invoices = \App\Models\Invoice::whereIn('id', $invoiceIds)
                                ->with(['customer', 'createdBy'])
                                ->orderBy('date', 'desc')
                                ->get();

                            // Generate Excel file using OpenSpout
                            $filename = 'selected-invoices-'.now()->format('Y-m-d_H-i-s').'.xlsx';

                            $headers = [
                                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
                            ];

                            $callback = function () use ($invoices) {
                                $writer = new \OpenSpout\Writer\XLSX\Writer;
                                $writer->openToFile('php://output');

                                // Add header row
                                $headerRow = \OpenSpout\Common\Entity\Row::fromValues([
                                    'Invoice Code',
                                    'Customer',
                                    'Date',
                                    'Total',
                                    'Paid',
                                    'Due',
                                    'Status',
                                    'Created By',
                                    'Created At',
                                ]);
                                $writer->addRow($headerRow);

                                // Add data rows
                                foreach ($invoices as $invoice) {
                                    $row = \OpenSpout\Common\Entity\Row::fromValues([
                                        $invoice->code,
                                        $invoice->customer?->name ?? '',
                                        $invoice->date?->format('Y-m-d'),
                                        (float) $invoice->total,
                                        (float) $invoice->paid,
                                        (float) $invoice->due,
                                        $invoice->status?->getLabel() ?? '',
                                        $invoice->createdBy?->name ?? 'System',
                                        $invoice->created_at?->format('Y-m-d H:i:s') ?? '',
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
                            $invoiceIds = $records->map(fn ($record) => $record['id'])->toArray();

                            $invoices = \App\Models\Invoice::whereIn('id', $invoiceIds)
                                ->with(['customer', 'createdBy'])
                                ->orderBy('date', 'desc')
                                ->get();

                            // Generate PDF
                            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.invoices-pdf', [
                                'invoices' => $invoices,
                                'filters' => $data['tableFilters'] ?? [],
                                'currentPage' => 1,
                                'totalPages' => 1,
                            ]);

                            $filename = 'selected-invoices-'.now()->format('Y-m-d_H-i-s').'.pdf';

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
            ->defaultSort('date', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->deferLoading()
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->persistSearchInSession();
    }
}
