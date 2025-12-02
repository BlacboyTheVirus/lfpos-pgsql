<?php

namespace App\Livewire\Customers;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Setting;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Livewire\Component;

class CustomerTransactionsModal extends Component implements HasActions, HasForms, HasTable
{
    use InteractsWithActions;
    use InteractsWithForms;
    use InteractsWithTable;

    public Customer $customer;

    public array $financialSummary = [];

    public function mount(Customer $customer): void
    {
        $this->customer = $customer;
        $this->calculateFinancialSummary();
    }

    protected function calculateFinancialSummary(): void
    {
        $invoices = $this->customer->invoices;

        // Cache currency settings to format amounts once
        $currencySettings = Setting::getCurrencySettings();
        $formatMoney = function (int $amountInCents) use ($currencySettings): string {
            $amount = $amountInCents;
            $formatted = number_format(
                $amount,
                (int) $currencySettings['decimal_places'],
                $currencySettings['decimal_separator'] ?? '.',
                $currencySettings['thousands_separator'] ?? ','
            );

            return $currencySettings['currency_position'] === 'before'
                ? $currencySettings['currency_symbol'].$formatted
                : $formatted.$currencySettings['currency_symbol'];
        };

        $totalInvoices = $invoices->sum('total');
        $totalPaid = $invoices->sum('paid');
        $totalDue = $invoices->sum('due');

        $this->financialSummary = [
            'total_invoices' => $totalInvoices,
            'total_invoices_formatted' => $formatMoney($totalInvoices),
            'total_paid' => $totalPaid,
            'total_paid_formatted' => $formatMoney($totalPaid),
            'total_due' => $totalDue,
            'total_due_formatted' => $formatMoney($totalDue),
            'invoice_count' => $invoices->count(),
        ];
    }

    public function table(Table $table): Table
    {
        // Cache currency settings once per table render to avoid repeated queries
        $currencySettings = Setting::getCurrencySettings();
        $formatMoney = function (int $amountInCents) use ($currencySettings): string {
            $amount = $amountInCents;
            $formatted = number_format(
                $amount,
                (int) $currencySettings['decimal_places'],
                $currencySettings['decimal_separator'] ?? '.',
                $currencySettings['thousands_separator'] ?? ','
            );

            return $currencySettings['currency_position'] === 'before'
                ? $currencySettings['currency_symbol'].$formatted
                : $formatted.$currencySettings['currency_symbol'];
        };

        return $table
            ->query(
                Invoice::query()
                    ->where('customer_id', $this->customer->id)
                    ->with(['createdBy'])
            )
            ->columns([
                TextColumn::make('row_number')
                    ->label('#')
                    ->rowIndex(),

                TextColumn::make('date')
                    ->label('Invoice Date')
                    ->date('d-m-Y')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('code')
                    ->label('Invoice Code')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Invoice code copied')
                    ->weight('medium'),

                TextColumn::make('total')
                    ->label('Invoice Total')
                    ->formatStateUsing(fn ($state) => $formatMoney($state))
                    ->alignment('right')
                    ->sortable()
                    ->weight('semibold'),

                TextColumn::make('paid')
                    ->label('Total Payment')
                    ->formatStateUsing(fn ($state) => $formatMoney($state))
                    ->alignment('right')
                    ->sortable(),

                TextColumn::make('due')
                    ->label('Amount Due')
                    ->formatStateUsing(fn ($state) => $formatMoney($state))
                    ->alignment('right')
                    ->sortable()
                    ->color(fn ($state) => $state > 0 ? '' : ''),

                TextColumn::make('status')
                    ->label('Payment Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state->getLabel())
                    ->color(fn ($state) => $state->getColor()),
            ])
            ->recordUrl(fn (Invoice $record) => route('filament.admin.resources.invoices.view', ['record' => $record->id]))
            ->recordActions([
                ActionGroup::make([
                    Action::make('view')
                        ->label('View Invoice')
                        ->icon('heroicon-o-eye')
                        ->color('info')
                        ->url(fn (Invoice $record) => route('filament.admin.resources.invoices.view', ['record' => $record->id])),

                    Action::make('edit')
                        ->label('Edit Invoice')
                        ->icon('heroicon-o-pencil')
                        ->color('warning')
                        ->url(fn (Invoice $record) => route('filament.admin.resources.invoices.edit', ['record' => $record->id])),

                    Action::make('print')
                        ->label('Print Invoice')
                        ->icon('heroicon-o-printer')
                        ->color('gray')
                        ->url(fn (Invoice $record) => route('filament.admin.resources.invoices.view', ['record' => $record->id]))
                        ->openUrlInNewTab(),
                ]),
            ])
            ->striped()
            ->defaultSort('date', 'desc')
            ->paginated([10, 25, 50])
            ->persistSearchInSession()
            ->persistSortInSession();
    }

    public function render()
    {
        return view('livewire.customers.customer-transactions-modal');
    }
}
