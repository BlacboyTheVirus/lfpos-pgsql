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

        $this->financialSummary = [
            'total_invoices' => $invoices->sum('total'),
            'total_paid' => $invoices->sum('paid'),
            'total_due' => $invoices->sum('due'),
            'invoice_count' => $invoices->count(),
        ];
    }

    public function table(Table $table): Table
    {
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
                    ->formatStateUsing(fn ($state) => Setting::formatMoney($state))
                    ->alignment('right')
                    ->sortable()
                    ->weight('semibold'),

                TextColumn::make('paid')
                    ->label('Total Payment')
                    ->formatStateUsing(fn ($state) => Setting::formatMoney($state))
                    ->alignment('right')
                    ->sortable(),

                TextColumn::make('due')
                    ->label('Amount Due')
                    ->formatStateUsing(fn ($state) => Setting::formatMoney($state))
                    ->alignment('right')
                    ->sortable()
                    ->color(fn ($state) => $state > 0 ? 'danger' : 'success'),

                TextColumn::make('status')
                    ->label('Payment Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state->getLabel())
                    ->color(fn ($state) => $state->getColor()),
            ])
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
