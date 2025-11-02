<div class="mb-4">
    <div class="flex flex-wrap gap-3">
        @foreach ($products as $product)
            <button
                type="button"
                wire:click="addProductToForm({{ $product->id }}, '{{ addslashes($product->name) }}', {{ $product->price }}, {{ $product->default_width ?? 1.0 }})"
                wire:loading.attr="disabled"
                class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-color-gray fi-btn-color-gray fi-size-md fi-btn-size-md gap-1.5 px-3 py-2 text-sm inline-grid shadow-sm bg-white text-gray-950 hover:bg-gray-50 dark:bg-white/5 dark:text-white dark:hover:bg-white/10 ring-1 ring-gray-950/10 dark:ring-white/20" style="margin-right: 0.75rem !important;">

                {{
                    \Filament\Support\generate_icon_html('heroicon-o-archive-box', null, (new \Illuminate\View\ComponentAttributeBag([
                        'wire:loading.remove' => true,
                        'wire:target' => "addProductToForm({$product->id}, '" . addslashes($product->name) . "', {$product->price}, " . ($product->default_width ?? 1.0) . ")",
                        'class' => 'fi-btn-icon h-5 w-5',
                    ])))
                }}

                <svg
                    wire:loading
                    wire:target="addProductToForm({{ $product->id }}, '{{ addslashes($product->name) }}', {{ $product->price }}, {{ $product->default_width ?? 1.0 }})"
                    fill="none"
                    viewBox="0 0 24 24"
                    xmlns="http://www.w3.org/2000/svg"
                    class="animate-spin fi-btn-icon h-5 w-5"
                >
                    <path
                        clip-rule="evenodd"
                        d="M12 19C15.866 19 19 15.866 19 12C19 8.13401 15.866 5 12 5C8.13401 5 5 8.13401 5 12C5 15.866 8.13401 19 12 19ZM12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22Z"
                        fill-rule="evenodd"
                        fill="currentColor"
                        opacity="0.2"
                    ></path>
                    <path
                        d="M2 12C2 6.47715 6.47715 2 12 2V5C8.13401 5 5 8.13401 5 12H2Z"
                        fill="currentColor"
                    ></path>
                </svg>

                <span class="fi-btn-label">
                    {{ $product->name }}
                </span>
            </button>
        @endforeach
    </div>
</div>
