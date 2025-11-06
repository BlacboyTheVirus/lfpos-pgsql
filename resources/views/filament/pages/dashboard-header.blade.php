<div class="flex flex-col gap-4" x-data="customDateRange()">
    <div>
        <h1 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white">
            Dashboard
        </h1>
    </div>

    <div class="flex flex-wrap items-center gap-2">
        {{-- Quick date range buttons --}}
        @foreach ([
            'all' => 'All Time',
            'today' => 'Today',
            'last_7_days' => 'Last 7 Days',
            'this_week' => 'This Week',
            'last_30_days' => 'Last 30 Days',
            'this_month' => 'This Month',
            'this_year' => 'This Year',
        ] as $value => $label)
            <button
                wire:click="setDateRange('{{ $value }}')"
                @class([
                    'px-4 py-2 text-sm font-medium rounded-lg transition',
                    'bg-primary-500 text-white hover:bg-primary-600' => $dateRange === $value,
                    'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600' => $dateRange !== $value,
                ])
            >
                {{ $label }}
            </button>
        @endforeach

        {{-- Custom Date Range Button --}}
        <button
            @click="showCustomDateRange = !showCustomDateRange"
            @class([
                'px-4 py-2 text-sm font-medium rounded-lg transition',
                'bg-primary-500 text-white hover:bg-primary-600' => $dateRange === 'custom',
                'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600' => $dateRange !== 'custom',
            ])
        >
            Custom Range
        </button>

        {{-- Reset Button --}}
        @if($dateRange !== 'all')
            <button
                wire:click="setDateRange('all')"
                class="px-4 py-2 text-sm font-medium rounded-lg transition bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
            >
                Reset
            </button>
        @endif
    </div>

    {{-- Custom Date Range Inputs --}}
    <div x-show="showCustomDateRange" x-cloak
         class="flex flex-wrap items-center gap-2 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">

        <div class="flex items-center gap-2">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">From:</label>
            <input
                type="date"
                x-model="customStartDate"
                @change="applyCustomDateRange()"
                class="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300"
                :max="customEndDate"
            >
        </div>

        <div class="flex items-center gap-2">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">To:</label>
            <input
                type="date"
                x-model="customEndDate"
                @change="applyCustomDateRange()"
                class="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300"
                :min="customStartDate"
                :max="today"
            >
        </div>

        <button
            @click="applyCustomDateRange()"
            class="px-4 py-2 text-sm font-medium rounded-lg transition bg-primary-500 text-white hover:bg-primary-600"
        >
            Apply
        </button>
    </div>
</div>

<script>
function customDateRange() {
    return {
        showCustomDateRange: false,
        customStartDate: '{{ $startDate ?? now()->subDays(30)->toDateString() }}',
        customEndDate: '{{ $endDate ?? now()->toDateString() }}',
        today: '{{ now()->toDateString() }}',

        init() {
            @if($dateRange === 'custom' && $startDate && $endDate)
                this.customStartDate = '{{ $startDate }}';
                this.customEndDate = '{{ $endDate }}';
                this.showCustomDateRange = true;
            @endif
        },

        applyCustomDateRange() {
            if (this.customStartDate && this.customEndDate) {
                @this.setStartDate(this.customStartDate);
                @this.setEndDate(this.customEndDate);
                @this.setDateRange('custom');
            }
        }
    }
}
</script>
