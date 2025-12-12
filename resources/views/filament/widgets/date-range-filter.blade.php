<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Date Range
        </x-slot>

        <div class="flex flex-wrap items-center gap-2" x-data>
            @foreach (['all' => 'All Time', 'today' => 'Today', 'last_7_days' => 'Last 7 Days', 'this_week' => 'This Week', 'last_30_days' => 'Last 30 Days', 'this_month' => 'This Month', 'this_year' => 'This Year'] as $value => $label)
                <button
                    @click="$dispatch('setDateRange', { range: '{{ $value }}' }); document.querySelector('[data-livewire-component]')._x_livewire.setDateRange('{{ $value }}')"
                    class="px-4 py-2 text-sm font-medium rounded-lg transition bg-gray-700 text-white hover:bg-gray-800 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
                >
                    {{ $label }}
                </button>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
