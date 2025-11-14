# Global Search Feature Implementation TODO

This guide documents how to recreate the custom global search feature for a Filament v4 + PostgreSQL project, based on the implementation in the Filament v3 + MySQL LFPOS project.

---

## Architecture Overview

This implementation uses a **custom Livewire modal component** instead of Filament's built-in global search, providing:
- Full control over search UI/UX
- Keyboard navigation (arrow keys, Enter, Escape)
- Recent searches tracking via session
- Quick actions and navigation shortcuts
- Custom result formatting with icons
- Cmd+K / Ctrl+K keyboard shortcut to open

---

## Implementation Checklist

### Phase 1: Setup & Configuration

#### 1. Disable Filament's Built-in Global Search
- [ ] Open your Panel Provider (e.g., `app/Providers/Filament/AdminPanelProvider.php`)
- [ ] In the `panel()` method configuration, disable global search:
```php
->globalSearch(false)  // Disables Filament's built-in global search
```

#### 2. Create Livewire Component Structure
- [ ] Create the Livewire component file:
```bash
php artisan make:livewire GlobalSearchModal
```

**Expected files:**
- `app/Livewire/GlobalSearchModal.php`
- `resources/views/livewire/global-search-modal.blade.php`

---

### Phase 2: Livewire Component Implementation

#### 3. Implement GlobalSearchModal Component
**File:** `app/Livewire/GlobalSearchModal.php`

- [ ] Add the component class properties:
```php
<?php

namespace App\Livewire;

use Filament\Facades\Filament;
use Filament\GlobalSearch\GlobalSearchResult;
use Filament\GlobalSearch\GlobalSearchResults;
use Illuminate\Support\Collection;
use Livewire\Component;

class GlobalSearchModal extends Component
{
    public bool $isOpen = false;
    public string $search = '';
    public int $selectedIndex = 0;
    public array $results = [];
    public array $quickActions = [];

    protected $listeners = ['open-global-search' => 'open'];
}
```

- [ ] Add the `open()` method to handle modal opening:
```php
public function open(): void
{
    $this->isOpen = true;
    $this->search = '';
    $this->selectedIndex = 0;
    $this->results = [];
    $this->quickActions = $this->getQuickActions();
}
```

- [ ] Add the `close()` method:
```php
public function close(): void
{
    $this->isOpen = false;
    $this->search = '';
    $this->selectedIndex = 0;
}
```

- [ ] Add the `updatedSearch()` lifecycle hook:
```php
public function updatedSearch(): void
{
    $this->selectedIndex = 0;

    if (strlen($this->search) >= 2) {
        $this->results = $this->getSearchResults();
    } else {
        $this->results = [];
        $this->quickActions = $this->getQuickActions();
    }
}
```

- [ ] Implement `getSearchResults()` method to search all resources:
```php
protected function getSearchResults(): array
{
    $panel = Filament::getCurrentPanel();
    $results = [];

    // Get all resources from the panel
    foreach ($panel->getResources() as $resource) {
        // Check if resource is globally searchable
        if (! $resource::canGloballySearch()) {
            continue;
        }

        // Get search results from resource
        $resourceResults = $resource::getGlobalSearchResults($this->search);

        if ($resourceResults->count() > 0) {
            $results[] = [
                'resource' => $resource::getModelLabel(),
                'results' => $this->formatResults($resourceResults),
            ];
        }
    }

    return $results;
}
```

- [ ] Implement `formatResults()` helper:
```php
protected function formatResults(GlobalSearchResults $results): array
{
    $formatted = [];

    foreach ($results->getResults() as $result) {
        $formatted[] = [
            'title' => $result->title,
            'url' => $result->url,
            'subtitle' => $this->formatSubtitle($result),
            'icon' => $this->getResourceIcon($result->resource::getModelLabel()),
        ];
    }

    return $formatted;
}
```

- [ ] Implement `formatSubtitle()` helper:
```php
protected function formatSubtitle(GlobalSearchResult $result): string
{
    $details = [];

    if ($result->details) {
        foreach ($result->details as $key => $value) {
            if ($value) {
                $details[] = "{$key}: {$value}";
            }
        }
    }

    // Return first 2 detail items joined with bullet separator
    return implode(' • ', array_slice($details, 0, 2));
}
```

- [ ] Implement `getResourceIcon()` helper:
```php
protected function getResourceIcon(string $resource): string
{
    return match (strtolower($resource)) {
        'customer' => 'heroicon-o-user-group',
        'product' => 'heroicon-o-cube',
        'invoice' => 'heroicon-o-document-text',
        'expense' => 'heroicon-o-banknotes',
        'user' => 'heroicon-o-users',
        'role' => 'heroicon-o-shield-check',
        default => 'heroicon-o-folder',
    };
}
```

- [ ] Implement `getQuickActions()` for navigation shortcuts:
```php
protected function getQuickActions(): array
{
    $panel = Filament::getCurrentPanel();
    $actions = [];

    // Navigation section
    $actions[] = [
        'section' => 'Navigation',
        'items' => [
            [
                'title' => 'Dashboard',
                'url' => $panel->getUrl(),
                'icon' => 'heroicon-o-home',
            ],
            [
                'title' => 'Customers',
                'url' => $panel->getUrl() . '/customers',
                'icon' => 'heroicon-o-user-group',
            ],
            [
                'title' => 'Products',
                'url' => $panel->getUrl() . '/products',
                'icon' => 'heroicon-o-cube',
            ],
            [
                'title' => 'Invoices',
                'url' => $panel->getUrl() . '/invoices',
                'icon' => 'heroicon-o-document-text',
            ],
            [
                'title' => 'Expenses',
                'url' => $panel->getUrl() . '/expenses',
                'icon' => 'heroicon-o-banknotes',
            ],
        ],
    ];

    // Quick actions section
    $actions[] = [
        'section' => 'Quick Actions',
        'items' => [
            [
                'title' => 'Create Invoice',
                'url' => $panel->getUrl() . '/invoices/create',
                'icon' => 'heroicon-o-plus-circle',
            ],
            [
                'title' => 'Manage Customers',
                'url' => $panel->getUrl() . '/customers',
                'icon' => 'heroicon-o-user-group',
            ],
            [
                'title' => 'Manage Products',
                'url' => $panel->getUrl() . '/products',
                'icon' => 'heroicon-o-cube',
            ],
            [
                'title' => 'Manage Expenses',
                'url' => $panel->getUrl() . '/expenses',
                'icon' => 'heroicon-o-banknotes',
            ],
        ],
    ];

    // Recent searches section
    $recentSearches = $this->getRecentSearches();
    if (! empty($recentSearches)) {
        $actions[] = [
            'section' => 'Recent Searches',
            'items' => array_map(function ($search) {
                return [
                    'title' => $search,
                    'url' => null,
                    'search' => $search,
                    'icon' => 'heroicon-o-clock',
                ];
            }, $recentSearches),
        ];
    }

    return $actions;
}
```

- [ ] Implement keyboard navigation methods:
```php
public function navigateDown(): void
{
    $totalItems = $this->getTotalSelectableItems();

    if ($totalItems > 0) {
        $this->selectedIndex = ($this->selectedIndex + 1) % $totalItems;
    }
}

public function navigateUp(): void
{
    $totalItems = $this->getTotalSelectableItems();

    if ($totalItems > 0) {
        $this->selectedIndex = $this->selectedIndex === 0
            ? $totalItems - 1
            : $this->selectedIndex - 1;
    }
}

protected function getTotalSelectableItems(): int
{
    if (strlen($this->search) >= 2) {
        $count = 0;
        foreach ($this->results as $group) {
            $count += count($group['results']);
        }
        return $count;
    }

    $count = 0;
    foreach ($this->quickActions as $section) {
        $count += count($section['items']);
    }
    return $count;
}

public function selectCurrent(): void
{
    if (strlen($this->search) >= 2) {
        $this->selectSearchResult();
    } else {
        $this->selectQuickAction();
    }
}

protected function selectSearchResult(): void
{
    $currentIndex = 0;

    foreach ($this->results as $group) {
        foreach ($group['results'] as $result) {
            if ($currentIndex === $this->selectedIndex) {
                $this->addToRecentSearches($this->search);
                $this->redirect($result['url']);
                return;
            }
            $currentIndex++;
        }
    }
}

protected function selectQuickAction(): void
{
    $currentIndex = 0;

    foreach ($this->quickActions as $section) {
        foreach ($section['items'] as $item) {
            if ($currentIndex === $this->selectedIndex) {
                if (isset($item['search'])) {
                    $this->search = $item['search'];
                    $this->updatedSearch();
                } else {
                    $this->redirect($item['url']);
                }
                return;
            }
            $currentIndex++;
        }
    }
}
```

- [ ] Implement recent searches tracking:
```php
protected function getRecentSearches(): array
{
    return array_slice(session('global_search_recent', []), 0, 5);
}

protected function addToRecentSearches(string $search): void
{
    $recent = session('global_search_recent', []);

    // Remove if already exists
    $recent = array_diff($recent, [$search]);

    // Add to beginning
    array_unshift($recent, $search);

    // Keep only last 10
    $recent = array_slice($recent, 0, 10);

    session(['global_search_recent' => $recent]);
}
```

- [ ] Add the `render()` method:
```php
public function render()
{
    return view('livewire.global-search-modal');
}
```

---

### Phase 3: Blade View Implementation

#### 4. Create the Modal View
**File:** `resources/views/livewire/global-search-modal.blade.php`

- [ ] Create the modal structure with Alpine.js:
```blade
<div
    x-data="{
        open: @entangle('isOpen'),
        init() {
            // Keyboard shortcut: Cmd+K or Ctrl+K
            document.addEventListener('keydown', (e) => {
                if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
                    e.preventDefault();
                    this.open = true;
                    this.$wire.call('open');
                }
            });
        }
    }"
    x-show="open"
    x-cloak
    class="fixed inset-0 z-50 overflow-y-auto"
    style="display: none;"
>
    <!-- Backdrop -->
    <div
        class="fixed inset-0 bg-black/50 dark:bg-black/70 transition-opacity"
        @click="open = false; $wire.call('close')"
    ></div>

    <!-- Modal Container -->
    <div class="flex min-h-full items-start justify-center p-4 sm:p-6 md:p-20">
        <div
            class="relative w-full max-w-2xl transform overflow-hidden rounded-xl bg-white dark:bg-gray-900 shadow-2xl ring-1 ring-black ring-opacity-5 transition-all"
            @click.away="open = false; $wire.call('close')"
        >
            <!-- Search Input -->
            <div class="relative border-b border-gray-200 dark:border-gray-700">
                <svg
                    class="pointer-events-none absolute left-4 top-3.5 h-5 w-5 text-gray-400 dark:text-gray-500"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>

                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    @keydown.down.prevent="$wire.call('navigateDown')"
                    @keydown.up.prevent="$wire.call('navigateUp')"
                    @keydown.enter.prevent="$wire.call('selectCurrent')"
                    @keydown.escape="open = false; $wire.call('close')"
                    class="h-12 w-full border-0 bg-transparent pl-11 pr-4 text-gray-900 dark:text-gray-100 placeholder:text-gray-400 dark:placeholder:text-gray-500 focus:ring-0 sm:text-sm"
                    placeholder="Search or type a command..."
                    autofocus
                    x-ref="searchInput"
                />

                <div class="absolute right-4 top-3 flex items-center gap-1">
                    <kbd class="rounded border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800 px-2 py-0.5 text-xs text-gray-600 dark:text-gray-400">
                        ESC
                    </kbd>
                </div>
            </div>

            <!-- Results -->
            <div class="max-h-96 overflow-y-auto p-2">
                @if(strlen($search) >= 2)
                    <!-- Search Results -->
                    @forelse($results as $group)
                        <div class="mb-4">
                            <!-- Section Header -->
                            <div class="sticky top-0 bg-white dark:bg-gray-900 px-3 py-2">
                                <h3 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ $group['resource'] }}
                                </h3>
                            </div>

                            <!-- Results List -->
                            <ul class="space-y-1">
                                @foreach($group['results'] as $index => $result)
                                    @php
                                        $globalIndex = collect($results)
                                            ->take(array_search($group, $results))
                                            ->sum(fn($g) => count($g['results'])) + $index;
                                    @endphp

                                    <li>
                                        <a
                                            href="{{ $result['url'] }}"
                                            wire:click="addToRecentSearches('{{ $search }}')"
                                            class="flex items-center gap-3 rounded-lg px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors {{ $globalIndex === $selectedIndex ? 'bg-gray-100 dark:bg-gray-800' : '' }}"
                                        >
                                            <x-filament::icon
                                                :icon="$result['icon']"
                                                class="h-5 w-5 text-gray-400 dark:text-gray-500"
                                            />

                                            <div class="flex-1 min-w-0">
                                                <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                    {{ $result['title'] }}
                                                </div>

                                                @if($result['subtitle'])
                                                    <div class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                                        {{ $result['subtitle'] }}
                                                    </div>
                                                @endif
                                            </div>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @empty
                        <div class="px-6 py-14 text-center text-sm">
                            <svg class="mx-auto h-6 w-6 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <p class="mt-4 text-gray-900 dark:text-gray-100">No results found</p>
                            <p class="mt-2 text-gray-500 dark:text-gray-400">Try searching for something else</p>
                        </div>
                    @endforelse
                @else
                    <!-- Quick Actions -->
                    @foreach($quickActions as $section)
                        <div class="mb-4">
                            <!-- Section Header -->
                            <div class="px-3 py-2">
                                <h3 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ $section['section'] }}
                                </h3>
                            </div>

                            <!-- Actions List -->
                            <ul class="space-y-1">
                                @foreach($section['items'] as $index => $item)
                                    @php
                                        $globalIndex = collect($quickActions)
                                            ->take(array_search($section, $quickActions))
                                            ->sum(fn($s) => count($s['items'])) + $index;
                                    @endphp

                                    <li>
                                        @if(isset($item['search']))
                                            <button
                                                wire:click="$set('search', '{{ $item['search'] }}')"
                                                type="button"
                                                class="w-full flex items-center gap-3 rounded-lg px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors text-left {{ $globalIndex === $selectedIndex ? 'bg-gray-100 dark:bg-gray-800' : '' }}"
                                            >
                                                <x-filament::icon
                                                    :icon="$item['icon']"
                                                    class="h-5 w-5 text-gray-400 dark:text-gray-500"
                                                />

                                                <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                    {{ $item['title'] }}
                                                </span>
                                            </button>
                                        @else
                                            <a
                                                href="{{ $item['url'] }}"
                                                class="flex items-center gap-3 rounded-lg px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors {{ $globalIndex === $selectedIndex ? 'bg-gray-100 dark:bg-gray-800' : '' }}"
                                            >
                                                <x-filament::icon
                                                    :icon="$item['icon']"
                                                    class="h-5 w-5 text-gray-400 dark:text-gray-500"
                                                />

                                                <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                    {{ $item['title'] }}
                                                </span>
                                            </a>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                @endif
            </div>

            <!-- Footer -->
            <div class="border-t border-gray-200 dark:border-gray-700 px-4 py-3 text-xs text-gray-500 dark:text-gray-400">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <span class="flex items-center gap-1">
                            <kbd class="rounded border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800 px-1.5 py-0.5">↑</kbd>
                            <kbd class="rounded border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800 px-1.5 py-0.5">↓</kbd>
                            to navigate
                        </span>
                        <span class="flex items-center gap-1">
                            <kbd class="rounded border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800 px-1.5 py-0.5">↵</kbd>
                            to select
                        </span>
                    </div>
                    <span>
                        <kbd class="rounded border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800 px-1.5 py-0.5">⌘</kbd>
                        <kbd class="rounded border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800 px-1.5 py-0.5">K</kbd>
                        to open
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>
```

---

### Phase 4: Panel Integration

#### 5. Integrate Modal into Panel Provider
**File:** `app/Providers/Filament/AdminPanelProvider.php`

- [ ] Import the modal component at the top of the file:
```php
use App\Livewire\GlobalSearchModal;
```

- [ ] Add render hooks in the `panel()` method:
```php
->renderHook(
    'panels::global-search.before',
    fn (): string => Blade::render(<<<'BLADE'
        <button
            type="button"
            wire:click="$dispatch('open-global-search')"
            class="flex items-center gap-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors w-64"
        >
            <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <span class="flex-1 text-left">Search...</span>
            <kbd class="rounded border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800 px-1.5 py-0.5 text-xs text-gray-500 dark:text-gray-400">
                ⌘K
            </kbd>
        </button>
    BLADE),
)
->renderHook(
    'panels::body.end',
    fn (): string => Blade::render('<livewire:global-search-modal />'),
)
```

---

### Phase 5: Make Resources Searchable

#### 6. Implement Global Search in Invoice Resource
**File:** `app/Filament/Resources/InvoiceResource.php`

- [ ] Add global search methods to the InvoiceResource class:
```php
public static function getGloballySearchableAttributes(): array
{
    return ['code', 'note', 'customer.name', 'customer.phone'];
}

public static function getGlobalSearchResultDetails(Model $record): array
{
    return [
        'Customer' => $record->customer->name,
        'Total' => '₦' . number_format($record->total, 2),
        'Status' => $record->status->getLabel(),
        'Date' => $record->date->format('M j, Y'),
    ];
}

public static function getGlobalSearchEloquentQuery(): Builder
{
    return parent::getGlobalSearchEloquentQuery()->with(['customer']);
}

public static function getGlobalSearchResultUrl(Model $record): string
{
    return static::getUrl('view', ['record' => $record]);
}
```

#### 7. Implement Global Search in Customer Resource
**File:** `app/Filament/Resources/CustomerResource.php`

- [ ] Add global search methods:
```php
public static function getGloballySearchableAttributes(): array
{
    return ['code', 'name', 'phone', 'email'];
}

public static function getGlobalSearchResultDetails(Model $record): array
{
    return [
        'Code' => $record->code,
        'Phone' => $record->phone,
        'Email' => $record->email,
    ];
}

public static function getGlobalSearchResultUrl(Model $record): string
{
    return static::getUrl('index') . '?tableAction=view&tableActionRecord=' . $record->getKey();
}
```

#### 8. Implement Global Search in Product Resource
**File:** `app/Filament/Resources/ProductResource.php`

- [ ] Add global search methods:
```php
public static function getGloballySearchableAttributes(): array
{
    return ['code', 'name', 'description', 'unit'];
}

public static function getGlobalSearchResultDetails(Model $record): array
{
    return [
        'Code' => $record->code,
        'Unit' => $record->unit,
        'Price' => '₦' . number_format($record->price, 2),
    ];
}
```

#### 9. Implement Global Search in Expense Resource
**File:** `app/Filament/Resources/ExpenseResource.php`

- [ ] Add global search methods:
```php
public static function getGloballySearchableAttributes(): array
{
    return ['code', 'description', 'category'];
}

public static function getGlobalSearchResultDetails(Model $record): array
{
    return [
        'Code' => $record->code,
        'Category' => $record->category->getLabel(),
        'Amount' => '₦' . number_format($record->amount, 2),
        'Date' => $record->date->format('M j, Y'),
    ];
}
```

---

### Phase 6: PostgreSQL-Specific Considerations

#### 10. Handle PostgreSQL Case Sensitivity
- [ ] **Note:** PostgreSQL is case-sensitive by default for LIKE searches, unlike MySQL
- [ ] If you need case-insensitive search, you have two options:

**Option A: Use ILIKE in Filament's search logic**
```php
// If Filament allows query customization
public static function getGlobalSearchEloquentQuery(): Builder
{
    return parent::getGlobalSearchEloquentQuery()
        ->where(function ($query) {
            // Custom ILIKE logic if needed
        });
}
```

**Option B: Create a custom search implementation**
- Override the search logic to use `ILIKE` instead of `LIKE`
- This may require deeper customization of Filament's GlobalSearch class

- [ ] Test search with various cases (uppercase, lowercase, mixed) to ensure it works as expected

#### 11. Index Strategy for PostgreSQL
- [ ] Consider adding GIN indexes for better full-text search performance:
```sql
-- Example migration
CREATE INDEX idx_customers_search ON customers USING GIN (
    to_tsvector('english', name || ' ' || phone || ' ' || email)
);
```

- [ ] **Alternative:** Use PostgreSQL's full-text search with `whereFullText()` if needed for better performance

---

### Phase 7: Filament v4 Migration Notes

#### 12. Check for Breaking Changes
- [ ] Review Filament v4 changelog for GlobalSearch API changes
- [ ] Verify these methods still exist:
    - `getGloballySearchableAttributes()`
    - `getGlobalSearchResultDetails()`
    - `getGlobalSearchEloquentQuery()`
    - `getGlobalSearchResultUrl()`
    - `canGloballySearch()`

- [ ] Check if `GlobalSearchResults` and `GlobalSearchResult` classes have changed
- [ ] Verify render hook names are still valid in v4:
    - `panels::global-search.before`
    - `panels::body.end`

#### 13. Update Dependencies
- [ ] Ensure Livewire is compatible with Filament v4
- [ ] Check if any Alpine.js syntax has changed
- [ ] Verify Heroicons are still available or update icon references

---

### Phase 8: Testing

#### 14. Functional Testing
- [ ] Test keyboard shortcut (Cmd+K / Ctrl+K) opens modal
- [ ] Test search with minimum 2 characters triggers results
- [ ] Test search with less than 2 characters shows quick actions
- [ ] Test arrow key navigation cycles through results correctly
- [ ] Test Enter key navigates to selected item
- [ ] Test Escape key closes modal
- [ ] Test clicking backdrop closes modal
- [ ] Test recent searches are saved and displayed
- [ ] Test clicking recent search populates search input

#### 15. Resource Testing
- [ ] Test searching for Invoice by code
- [ ] Test searching for Invoice by customer name
- [ ] Test searching for Customer by name, phone, email
- [ ] Test searching for Product by name, description
- [ ] Test searching for Expense by description, category
- [ ] Test result details display correctly
- [ ] Test result URLs navigate to correct pages

#### 16. PostgreSQL Testing
- [ ] Test case-sensitive searches work as expected
- [ ] Test special characters in search terms
- [ ] Test searches with accented characters (if applicable)
- [ ] Monitor query performance with EXPLAIN ANALYZE
- [ ] Verify indexes are being used

#### 17. UI/UX Testing
- [ ] Test dark mode styling
- [ ] Test responsive design on mobile/tablet
- [ ] Test with long result titles/subtitles
- [ ] Test with many results (scrolling behavior)
- [ ] Test with no results (empty state)
- [ ] Test icon display for all resource types

---

### Phase 9: Optimization & Polish

#### 18. Performance Optimization
- [ ] Add debouncing to search input (300ms recommended)
- [ ] Limit search results per resource (e.g., max 5 per resource)
- [ ] Add loading states for slow searches
- [ ] Consider caching recent searches in Redis instead of session
- [ ] Add pagination for large result sets

#### 19. Accessibility
- [ ] Test keyboard navigation with screen readers
- [ ] Add ARIA labels to modal elements
- [ ] Ensure focus management (focus search input on open)
- [ ] Add skip links if needed
- [ ] Test with keyboard-only navigation

#### 20. Documentation
- [ ] Document keyboard shortcuts for users
- [ ] Add comments to complex code sections
- [ ] Create user guide for search features
- [ ] Document which resources are searchable

---

## Summary of Key Files

| File | Purpose |
|------|---------|
| `app/Livewire/GlobalSearchModal.php` | Main Livewire component handling search logic |
| `resources/views/livewire/global-search-modal.blade.php` | Modal UI with Alpine.js |
| `app/Providers/Filament/AdminPanelProvider.php` | Panel configuration and render hooks |
| `app/Filament/Resources/InvoiceResource.php` | Invoice search implementation |
| `app/Filament/Resources/CustomerResource.php` | Customer search implementation |
| `app/Filament/Resources/ProductResource.php` | Product search implementation |
| `app/Filament/Resources/ExpenseResource.php` | Expense search implementation |

---

## Additional Customization Ideas

- [ ] Add search filters (e.g., "type:invoice", "status:paid")
- [ ] Add search history with timestamps
- [ ] Add "Clear recent searches" button
- [ ] Add search analytics tracking
- [ ] Add voice search support
- [ ] Add search result highlights
- [ ] Add fuzzy search for typos
- [ ] Add search suggestions/autocomplete

---

## PostgreSQL vs MySQL Key Differences

| Feature | MySQL | PostgreSQL |
|---------|-------|------------|
| **LIKE Operator** | Case-insensitive by default | Case-sensitive |
| **Alternative** | LIKE | ILIKE (case-insensitive) |
| **Full-Text Search** | MATCH AGAINST | to_tsvector() |
| **JSON Search** | JSON_EXTRACT() | jsonb operators |
| **Regex** | REGEXP | ~ or ~* |

---

## Troubleshooting

### Issue: Search not working
- [ ] Check if `globalSearch(false)` is set in panel config
- [ ] Verify resources have `getGloballySearchableAttributes()` method
- [ ] Check if `canGloballySearch()` returns true
- [ ] Verify Livewire component is loaded

### Issue: Recent searches not saving
- [ ] Check session configuration
- [ ] Verify session driver is working
- [ ] Check browser cookies/storage

### Issue: Icons not displaying
- [ ] Verify Heroicons package is installed
- [ ] Check icon names match available icons
- [ ] Verify Blade component syntax is correct

### Issue: PostgreSQL case-sensitivity
- [ ] Use ILIKE instead of LIKE
- [ ] Add custom search query logic
- [ ] Consider using full-text search indexes

---

## Credits & References

- **Original Implementation:** LFPOS Project (Filament v3 + MySQL)
- **Filament Docs:** https://filamentphp.com/docs
- **Livewire Docs:** https://livewire.laravel.com
- **Alpine.js Docs:** https://alpinejs.dev
- **PostgreSQL Docs:** https://www.postgresql.org/docs/

---

**Version:** 1.0
**Last Updated:** 2025-01-13
**Compatible With:** Filament v4, PostgreSQL 12+, Laravel 11+
