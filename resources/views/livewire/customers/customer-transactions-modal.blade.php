<div class="p-6">
    {{-- Two Column Layout --}}
    <div style="width: 100%;">
        <div class="grid gap-6 mb-6" style="display: grid !important; grid-template-columns: 1fr 1fr !important;">
            {{-- Left Column: Customer Information --}}
            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Customer Information</h3>
            <div class="space-y-3">
                <div class="text-2xl font-bold text-gray-900 dark:text-white" style="font-size: 1.5rem;">{{ $customer->name }}</div>
                <div class="text-sm text-gray-600 dark:text-gray-400" style="padding-bottom: .175rem;">{{ $customer->code }}</div>

                <div class="space-y-2 pt-2">
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        <span class="font-medium">Phone: </span> {{ $customer->phone ?: 'N/A' }}
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        <span class="font-medium">Email: </span> {{ $customer->email ?: 'N/A' }}
                    </div>
                </div>
            </div>
        </div>

        {{-- Right Column: Financial Summary --}}
        <div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Financial Summary</h3>
            <div class="space-y-2">
                <div  style="display: flex; justify-content: space-between; align-items: center; padding: .175rem 0;">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Total Invoices:</span>
                    <span class="text-sm font-semibold text-gray-900 dark:text-white">
                        {{ $financialSummary['total_invoices_formatted'] }}
                    </span>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; padding: .175rem 0;">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Amount Paid:</span>
                    <span class="text-sm font-semibold text-gray-900 dark:text-white">
                        {{ $financialSummary['total_paid_formatted'] }}
                    </span>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; padding: .175rem 0;">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Amount Due:</span>
                    <span class="text-sm font-semibold text-gray-900 dark:text-white">
                        {{ $financialSummary['total_due_formatted'] }}
                    </span>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; padding: .175rem 0;">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Invoice Count:</span>
                    <span class="text-sm font-semibold text-gray-900 dark:text-white">
                        {{ number_format($financialSummary['invoice_count']) }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{-- Filament Table --}}
    <div class="mt-6">
        {{ $this->table }}
    </div>
</div>
