<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Dashboard;
use App\Filament\Resources\Invoices\InvoiceResource;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->sidebarCollapsibleOnDesktop()
            ->colors([
                'primary' => "#16a34a",
            ])
            ->brandName(('LfPOS'))
            ->brandLogo(asset('logo.png'))
            ->brandLogoHeight(('2rem'))
            ->darkModeBrandLogo(asset('logo_dark.png'))
            ->favicon(asset('favicon.png'))
            ->globalSearch(false)
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->sidebarWidth('15rem')
            // ->maxContentWidth(Width::SevenExtraLarge)
            ->maxContentWidth(Width::SevenExtraLarge)
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([])
            ->plugins([
                FilamentShieldPlugin::make()
                    ->navigationGroup('System')
                    ->navigationSort(70),
            ])
            ->assets([
                Css::make('admin-fixes', asset('css/admin-fixes.css')),
                Js::make('app-js', asset('build/assets/app-D70hdtKS.js')),
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->renderHook(
                PanelsRenderHook::GLOBAL_SEARCH_BEFORE,
                fn (): string => Blade::render('
                    <x-filament::button
                        href="{{ route(\'filament.admin.resources.invoices.create\') }}"
                        icon="heroicon-o-document-text"
                        color="primary"
                        tag="a"
                        data-shortcut="i"
                        class="universal-create me-4"
                    >
                        Create Invoice
                    </x-filament::button>
                    <button
                        onclick="Livewire.dispatch(\'open-global-search\')"
                        class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-color-gray fi-btn-color-gray fi-size-md fi-btn-size-md gap-1.5 px-3 py-2 text-sm inline-grid shadow-sm bg-white text-gray-950 hover:bg-gray-50 focus-visible:ring-primary-500/50 dark:bg-white/5 dark:text-white dark:hover:bg-white/10 dark:focus-visible:ring-primary-400/50 fi-ac-action fi-ac-btn-action me-4 border border-gray-300 dark:border-gray-600">
                        <x-heroicon-o-magnifying-glass class="fi-btn-icon transition duration-75 h-4 w-4" />
                        <span class="fi-btn-label">Search</span>
                        <span class="text-xs text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-800 px-1.5 py-0.5 rounded ml-2">⌘K</span>
                    </button>

                    <!-- Hidden navigation links for keyboard shortcuts -->
                    <a href="{{ route(\'filament.admin.resources.expenses.index\') }}"
                       data-shortcut="e"
                       class="hidden"
                       aria-label="Navigate to Expenses"></a>
                    <a href="{{ route(\'filament.admin.resources.customers.index\') }}"
                       data-shortcut="u"
                       class="hidden"
                       aria-label="Navigate to Customers"></a>
                    <a href="{{ route(\'filament.admin.resources.products.index\') }}"
                       data-shortcut="d"
                       class="hidden"
                       aria-label="Navigate to Products"></a>
                ')
            )
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn (): string => Blade::render('<livewire:global-search-modal />').'
                <script>
                    // Global keyboard shortcut for Create action (Cmd+Option+N or Ctrl+Alt+N)
                    console.log("Keyboard shortcut script loaded globally!");

                    document.addEventListener("keydown", function(event) {
                        // Check for Cmd+Option+N (Mac) or Ctrl+Alt+N (Windows/Linux)
                        // Use event.code instead of event.key because Cmd+Option+N produces a special character on Mac
                        if (event.code === "KeyN") {
                            if ((event.metaKey && event.altKey) || (event.ctrlKey && event.altKey)) {
                                console.log("Keyboard shortcut triggered!");
                                event.preventDefault();

                                // Try to find the create button/link in header actions and click it
                                let createButton = null;

                                // Try to find in the header actions area first
                                const headerActions = document.querySelector(".fi-header-actions, [class*=\'header-actions\']");
                                if (headerActions) {
                                    // Look for any button or link that might be the create action
                                    createButton = headerActions.querySelector("button, a");
                                    console.log("Found button in header actions:", createButton);
                                }

                                // Fallback: try other selectors
                                if (!createButton) {
                                    createButton = document.querySelector(
                                        "button[wire\\\\:click*=\'mountAction\']," +
                                        "a[wire\\\\:click*=\'mountAction\']," +
                                        "button[type=\'button\'][wire\\\\:click]," +
                                        "a[href*=\'create\']"
                                    );
                                }

                                if (createButton) {
                                    console.log("Found create button, clicking it:", createButton);
                                    createButton.click();
                                    return;
                                }

                                console.log("Create button not found, trying Livewire approach");

                                // Fallback: Find the Livewire component and call mountAction
                                const livewireElement = document.querySelector("[wire\\\\:id]");

                                if (livewireElement) {
                                    const componentId = livewireElement.getAttribute("wire:id");
                                    console.log("Component ID:", componentId);
                                    const livewireComponent = Livewire.find(componentId);

                                    if (livewireComponent) {
                                        console.log("Livewire component found, calling mountAction");
                                        try {
                                            livewireComponent.call("mountAction", "create");
                                            console.log("mountAction called successfully");
                                        } catch (error) {
                                            console.error("Error calling mountAction:", error);
                                        }
                                    } else {
                                        console.error("Livewire component not found for ID:", componentId);
                                    }
                                } else {
                                    console.error("No Livewire element found on page");
                                }
                            }
                        }
                    });
                </script>
                <script>
                    document.addEventListener("DOMContentLoaded", function() {
                        // Global Search Slide Effect
                        function initializeGlobalSearchSlideEffect() {
                            // Remove any existing style injection to avoid duplicates
                            const existingStyle = document.querySelector("#global-search-slide-fix");
                            if (existingStyle) {
                                existingStyle.remove();
                            }

                            // Inject the working CSS with ultra-high specificity
                            const style = document.createElement("style");
                            style.id = "global-search-slide-fix";
                            style.textContent = `
                                /* Ultra-high specificity slide effect CSS - PERMANENT FIX */
                                html body div[data-headlessui-state] .global-search-result,
                                html body [role="dialog"] .global-search-result,
                                html body .global-search-result {
                                    transition: transform 0.2s ease, background-color 0.2s ease !important;
                                    transform: translateX(0px) !important;
                                    position: relative !important;
                                    border-left: 2px solid transparent !important;
                                }

                                /* Ultra-high specificity hover effect - PERMANENT FIX */
                                html body div[data-headlessui-state] .global-search-result:hover,
                                html body [role="dialog"] .global-search-result:hover,
                                html body .global-search-result:hover {
                                    transform: translateX(8px) !important;
                                    background-color: #f3f3f3 !important;
                                    border-left: 2px solid rgba(20, 184, 166, 1) !important;
                                }

                                .dark .global-search-result:hover {
                                    transform: translateX(8px) !important;
                                    background-color: #1e1e1e !important;
                                    border-left: 2px solid #242426 !important;
                                }
                            `;
                            document.head.appendChild(style);

                            console.log("✅ Global search slide effect CSS applied successfully");
                        }

                        // Initialize on page load
                        initializeGlobalSearchSlideEffect();

                        // Re-initialize on Livewire navigations
                        document.addEventListener("livewire:navigated", function() {
                            initializeGlobalSearchSlideEffect();
                        });

                        // Re-initialize on Livewire updates (for dynamic content)
                        document.addEventListener("livewire:updated", function() {
                            initializeGlobalSearchSlideEffect();
                        });
                    });
                </script>'
            );
    }
}
