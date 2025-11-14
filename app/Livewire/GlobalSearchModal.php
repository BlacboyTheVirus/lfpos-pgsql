<?php

namespace App\Livewire;

use Filament\GlobalSearch\GlobalSearchResult;
use Filament\GlobalSearch\GlobalSearchResults;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;
use Livewire\Component;

class GlobalSearchModal extends Component
{
    public string $search = '';

    public bool $isOpen = false;

    public int $selectedIndex = 0;

    public array $recentSearches = [];

    public function mount()
    {
        $this->recentSearches = session('global_search_recent', []);
    }

    public function updatedSearch()
    {
        $this->selectedIndex = 0;
    }

    #[On('open-global-search')]
    public function open()
    {
        $this->isOpen = true;
        $this->search = '';
        $this->selectedIndex = 0;
    }

    public function close()
    {
        $this->isOpen = false;
        $this->search = '';
    }

    public function selectResult($url)
    {
        if (! empty($this->search)) {
            $this->addToRecentSearches($this->search);
        }

        $this->close();
        $this->redirect($url);
    }

    public function navigateUp()
    {
        if ($this->selectedIndex > 0) {
            $this->selectedIndex--;
        }
    }

    public function navigateDown()
    {
        $results = $this->getSearchResults();
        $totalResults = $results->sum(fn ($category) => count($category['results']));

        if ($this->selectedIndex < $totalResults - 1) {
            $this->selectedIndex++;
        }
    }

    public function selectCurrent()
    {
        $results = $this->getSearchResults();
        $flatResults = [];

        foreach ($results as $category) {
            foreach ($category['results'] as $result) {
                $flatResults[] = $result;
            }
        }

        if (isset($flatResults[$this->selectedIndex])) {
            $this->selectResult($flatResults[$this->selectedIndex]['url']);
        }
    }

    public function getSearchResults(): Collection
    {
        if (strlen($this->search) < 2) {
            return $this->getQuickActions();
        }

        $globalSearchResults = new GlobalSearchResults;

        // Get resources using Filament facade
        $panel = \Filament\Facades\Filament::getCurrentPanel();

        // Search in all registered resources
        foreach ($panel->getResources() as $resource) {
            if (! $resource::canGloballySearch()) {
                continue;
            }

            $resourceResults = $resource::getGlobalSearchResults($this->search);

            if ($resourceResults->count()) {
                $globalSearchResults->category(
                    $resource::getModelLabel(),
                    $resourceResults
                );
            }
        }

        return $this->formatResults($globalSearchResults);
    }

    protected function getQuickActions(): Collection
    {
        $quickActions = collect([
            [
                'title' => 'Navigation',
                'icon' => 'heroicon-o-squares-2x2',
                'results' => [
                    [
                        'title' => 'Dashboard',
                        'subtitle' => 'Overview and analytics',
                        'url' => $this->getRouteUrl('filament.admin.pages.dashboard'),
                        'icon' => 'heroicon-o-home',
                    ],
                    [
                        'title' => 'Customers',
                        'subtitle' => 'Manage customer records',
                        'url' => $this->getRouteUrl('filament.admin.resources.customers.index'),
                        'icon' => 'heroicon-o-user-group',
                    ],
                    [
                        'title' => 'Products',
                        'subtitle' => 'Product catalog management',
                        'url' => $this->getRouteUrl('filament.admin.resources.products.index'),
                        'icon' => 'heroicon-o-cube',
                    ],
                    [
                        'title' => 'Invoices',
                        'subtitle' => 'Invoice management',
                        'url' => $this->getRouteUrl('filament.admin.resources.invoices.index'),
                        'icon' => 'heroicon-o-document-text',
                    ],
                    [
                        'title' => 'Expenses',
                        'subtitle' => 'Track business expenses',
                        'url' => $this->getRouteUrl('filament.admin.resources.expenses.index'),
                        'icon' => 'heroicon-o-banknotes',
                    ],
                ],
            ],
        ]);

        // Add Quick Actions section
        $quickActionsResults = [
            [
                'title' => 'Create Invoice',
                'subtitle' => 'Create a new invoice',
                'url' => $this->getRouteUrl('filament.admin.resources.invoices.create'),
                'icon' => 'heroicon-o-document-plus',
            ],
            [
                'title' => 'Manage Customers',
                'subtitle' => 'Add or edit customers',
                'url' => $this->getRouteUrl('filament.admin.resources.customers.index'),
                'icon' => 'heroicon-o-user-plus',
            ],
            [
                'title' => 'Manage Products',
                'subtitle' => 'Add or edit products',
                'url' => $this->getRouteUrl('filament.admin.resources.products.index'),
                'icon' => 'heroicon-o-plus-circle',
            ],
            [
                'title' => 'Manage Expenses',
                'subtitle' => 'Add or edit expenses',
                'url' => $this->getRouteUrl('filament.admin.resources.expenses.index'),
                'icon' => 'heroicon-o-minus-circle',
            ],
        ];

        $quickActions->push([
            'title' => 'Quick Actions',
            'icon' => 'heroicon-o-bolt',
            'results' => $quickActionsResults,
        ]);

        if (! empty($this->recentSearches)) {
            $quickActions->prepend([
                'title' => 'Recent Searches',
                'icon' => 'heroicon-o-clock',
                'results' => collect($this->recentSearches)->take(5)->map(fn ($search) => [
                    'title' => "Search: {$search}",
                    'subtitle' => 'Previous search query',
                    'url' => '#',
                    'icon' => 'heroicon-o-magnifying-glass',
                    'action' => 'fillSearch',
                    'search' => $search,
                ])->toArray(),
            ]);
        }

        return $quickActions;
    }

    protected function formatResults(GlobalSearchResults $globalSearchResults): Collection
    {
        $formatted = collect();

        foreach ($globalSearchResults->getCategories() as $group => $results) {
            $categoryResults = [];

            foreach ($results as $result) {
                $categoryResults[] = [
                    'title' => $result->title,
                    'subtitle' => $this->formatSubtitle($result),
                    'url' => $result->url,
                    'icon' => $this->getResourceIcon($group),
                ];
            }

            if (! empty($categoryResults)) {
                $formatted->push([
                    'title' => $group,
                    'icon' => $this->getResourceIcon($group),
                    'results' => $categoryResults,
                ]);
            }
        }

        return $formatted;
    }

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

        return implode(' • ', array_slice($details, 0, 2));
    }

    protected function getResourceIcon(string $resource): string
    {
        return match (strtolower($resource)) {
            'customer' => 'heroicon-o-user-group',
            'product' => 'heroicon-o-cube',
            'invoice' => 'heroicon-o-document-text',
            'expense' => 'heroicon-o-banknotes',
            default => 'heroicon-o-folder',
        };
    }

    public function fillSearch($search)
    {
        $this->search = $search;
    }

    protected function addToRecentSearches(string $search)
    {
        $recent = collect(session('global_search_recent', []))
            ->reject(fn ($item) => $item === $search)
            ->prepend($search)
            ->take(10)
            ->values()
            ->toArray();

        session(['global_search_recent' => $recent]);
        $this->recentSearches = $recent;
    }

    protected function routeExists(string $routeName): bool
    {
        try {
            route($routeName);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    protected function getRouteUrl(string $routeName): string
    {
        try {
            return route($routeName);
        } catch (\Exception $e) {
            return '/admin';
        }
    }

    public function render()
    {
        return view('livewire.global-search-modal', [
            'results' => $this->getSearchResults(),
        ]);
    }
}
