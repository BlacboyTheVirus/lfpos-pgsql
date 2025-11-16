<div x-data="{
    isOpen: @entangle('isOpen'),
    selectedIndex: @entangle('selectedIndex'),
    initModal() {
        document.addEventListener('keydown', (e) => {
            // Open modal with Cmd+K or Ctrl+K
            if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
                e.preventDefault();
                this.$dispatch('open-global-search');
            }

            // Handle modal navigation when open
            if (this.isOpen) {
                if (e.key === 'Escape') {
                    e.preventDefault();
                    $wire.close();
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    $wire.navigateUp();
                } else if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    $wire.navigateDown();
                } else if (e.key === 'Enter') {
                    e.preventDefault();
                    $wire.selectCurrent();
                }
            }
        });

        // Watch for modal open state and focus input
        this.$watch('isOpen', value => {
            if (value) {
                this.$nextTick(() => {
                    setTimeout(() => {
                        const input = this.$refs.searchInput;
                        if (input) {
                            input.focus();
                            input.select();
                        }
                    }, 150);
                });
            }
        });
    }
}" x-init="initModal()">

    {{-- Modal Container --}}
    <div x-show="isOpen"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-start justify-center pt-24"
         style="display: none;"
         @click="$wire.close()">

        {{-- Backdrop --}}
        <div class="global-search-backdrop fixed inset-0 bg-black/50 backdrop-blur-sm"></div>

        {{-- Modal Content --}}
        <div x-transition:enter="transition ease-out duration-200 transform"
             x-transition:enter-start="opacity-0 scale-95 translate-y-4"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150 transform"
             x-transition:leave-start="opacity-100 scale-100 translate-y-0"
             x-transition:leave-end="opacity-0 scale-95 translate-y-4"
             class="global-search-container w-full max-w-2xl mx-4 relative z-10"
             style="margin-top: 3rem !important;"
             @click.stop>
            {{-- Search Input --}}
            <div class="global-search-input-container bg-white dark:bg-gray-900 rounded-t-xl shadow-xl">
                <div class="flex items-center px-4 py-3.5">
                    <x-heroicon-o-magnifying-glass class="h-5 w-5 text-gray-400 dark:text-gray-100 mr-3 shrink-0" />
                    <input
                        wire:model.live.debounce.300ms="search"
                        type="text"
                        placeholder="Type a command or search..."
                        class="flex-1 bg-transparent border-0 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-100 focus:ring-0 focus:outline-none text-base"
                        x-ref="searchInput"
                        style="font-size: 1.25rem;line-height: 1rem;border-width: 0px !important; margin: 1rem;padding: 0.5rem !important;width: 100%;"
                        x-init="setTimeout(() => $refs.searchInput.focus(), 100)"
                    />
                    <div class="text-xs text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded font-medium">
                        esc
                    </div>
                </div>
            </div>

            {{-- Search Results --}}
            <div class="global-search-results bg-white dark:bg-gray-900 rounded-b-xl shadow-xl max-h-96 overflow-y-auto">
                @if($results->isEmpty())
                    {{-- Empty State --}}
                    <div class="p-8 text-center align-middle" >
                        <x-heroicon-o-magnifying-glass class="h-5 w-5 text-gray-300 dark:text-gray-600 mx-auto mb-4 text-center" style="margin: auto"/>
                        <p class="text-gray-600 dark:text-gray-400 text-base font-medium">No results found</p>
                        <p class="text-gray-500 dark:text-gray-500 text-sm mt-1">Try adjusting your search terms</p>
                    </div>
                @else
                    @php $globalIndex = 0; @endphp
                    @foreach($results as $category)
                        {{-- Category Header --}}
                        <div class="sticky top-0 bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-4 py-2.5 text-xs font-semibold text-gray-600 dark:text-gray-400  uppercase tracking-wide  z-50" style="font-size: 0.75rem; padding: 0.5rem;">
                            <div class="flex items-center">
                                <x-dynamic-component :component="$category['icon']" class="h-4 w-4 mr-2.5 text-gray-500 dark:text-gray-500" />
                                {{ $category['title'] }}
                            </div>
                        </div>

                        {{-- Category Results --}}
                        @foreach($category['results'] as $result)
                            <div wire:click="selectResult('{{ $result['url'] }}')"
                                 class="global-search-result flex items-center px-4 py-3 cursor-pointer border-b border-gray-100 dark:border-gray-800 last:border-b-0 {{ $globalIndex === $selectedIndex ? 'bg-teal-50 dark:bg-teal-900/10 border-teal-200 dark:border-teal-800/30' : '' }}">

                                <div class="shrink-0 mr-3">
                                    <div class="w-8 h-8 rounded-lg bg-transparent flex items-center justify-center" style="padding: 0.5rem; border: 1px solid rgb(161 161 170); margin-right: 1rem">
                                        <x-dynamic-component :component="$result['icon']" class="h-4 w-4 text-gray-500 dark:text-gray-400"  style="color: rgb(161 161 170)"/>
                                    </div>
                                </div>

                                <div class="flex-1 min-w-0">
                                    <div class="search-main  font-medium text-gray-500 dark:text-gray-400 truncate {{ $globalIndex === $selectedIndex ? 'text-gray-900 dark:text-gray-300' : '' }}">
                                        {{ $result['title'] }}
                                    </div>
                                    @if(isset($result['subtitle']) && $result['subtitle'])
                                        <div class="search-description text-xs text-gray-500 dark:text-gray-400 truncate mt-0.5">
                                            {{ $result['subtitle'] }}
                                        </div>
                                    @endif
                                </div>

                            </div>
                            @php $globalIndex++; @endphp
                        @endforeach
                    @endforeach
                @endif

                {{-- Footer --}}
                @if($results->isNotEmpty())
                    <div class="px-4 py-3  dark:bg-gray-800/90 border-t border-gray-200 dark:border-gray-700 text-xs text-gray-600 dark:text-gray-400" style="font-size: 0.75rem; padding: 0.875rem;">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-4">
                                <div class="flex items-center gap-1.5">
                                    <span class="inline-flex items-center px-1.5 py-0.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded text-xs font-medium">↑↓</span>
                                    <span>to navigate</span>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <span class="inline-flex items-center px-1.5 py-0.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded text-xs font-medium">↵</span>
                                    <span>to select</span>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <span class="inline-flex items-center px-1.5 py-0.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded text-xs font-medium">esc</span>
                                    <span>to close</span>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
