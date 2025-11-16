/**
 * Keyboard Shortcut Indicators
 * Shows keyboard shortcuts in buttons when Cmd/Ctrl key is held down
 */

class ShortcutIndicators {
    constructor() {
        this.isModifierPressed = false;
        this.isMac = navigator.platform.toUpperCase().indexOf('MAC') >= 0;
        this.modifierKey = this.isMac ? 'metaKey' : 'ctrlKey';
        this.modifierSymbol = this.isMac ? '⌘' : 'Ctrl';

        this.init();
    }

    init() {
        this.bindEvents();
        this.setupShortcuts();
        this.addShortcutAttributesToNavigation();
    }

    addShortcutAttributesToNavigation() {
        // Map navigation URLs to their shortcuts
        const navigationShortcuts = {
            '/admin/expenses': 'e',
            '/admin/customers': 'u',
            '/admin/products': 'd'
        };

        // Find and add data-shortcut attributes to sidebar navigation links
        Object.entries(navigationShortcuts).forEach(([url, shortcut]) => {
            // Find the navigation link - look for links in the sidebar
            const navLinks = document.querySelectorAll('nav a[href*="' + url + '"]');
            navLinks.forEach(link => {
                // Only add to sidebar navigation links, not breadcrumbs or other links
                if (link.closest('aside') || link.closest('nav[role="navigation"]')) {
                    link.setAttribute('data-shortcut', shortcut);
                }
            });
        });
    }

    bindEvents() {
        // Listen for modifier key press/release
        document.addEventListener('keydown', (e) => {
            if (e[this.modifierKey] && !this.isModifierPressed) {
                this.isModifierPressed = true;
                this.showShortcuts();
            }
        });

        document.addEventListener('keyup', (e) => {
            if (!e[this.modifierKey] && this.isModifierPressed) {
                this.isModifierPressed = false;
                this.hideShortcuts();
            }
        });

        // Handle window blur to reset state
        window.addEventListener('blur', () => {
            if (this.isModifierPressed) {
                this.isModifierPressed = false;
                this.hideShortcuts();
            }
        });
    }

    setupShortcuts() {
        // Define all keyboard shortcuts
        const shortcuts = {
            'i': '[data-shortcut="i"]',     // Create Invoice
            'e': '[data-shortcut="e"]',     // Expenses
            'u': '[data-shortcut="u"]',     // Customers (Users)
            'd': '[data-shortcut="d"]'      // Products (proDucts)
        };

        document.addEventListener('keydown', (e) => {
            if (e[this.modifierKey]) {
                const key = e.key.toLowerCase();

                if (shortcuts[key]) {
                    e.preventDefault();
                    const element = document.querySelector(shortcuts[key]);
                    if (element) {
                        element.click();
                    }
                }
            }
        });
    }

    showShortcuts() {
        document.body.classList.add('shortcuts-visible');

        // Add shortcut indicators to buttons with data-shortcut attribute
        const shortcutButtons = document.querySelectorAll('[data-shortcut]');

        shortcutButtons.forEach(button => {
            const shortcutKey = button.getAttribute('data-shortcut');
            if (shortcutKey && !button.querySelector('.shortcut-indicator')) {
                this.addShortcutIndicator(button, shortcutKey);
            }
        });

        // Add indicator to global search if it exists
        const globalSearch = document.querySelector('[data-global-search-input]');
        if (globalSearch && !globalSearch.querySelector('.shortcut-indicator')) {
            const wrapper = globalSearch.closest('.fi-global-search-field') || globalSearch.parentElement;
            if (wrapper) {
                this.addShortcutIndicator(wrapper, 'K', true);
            }
        }
    }

    hideShortcuts() {
        document.body.classList.remove('shortcuts-visible');

        // Remove all shortcut indicators
        const indicators = document.querySelectorAll('.shortcut-indicator');
        indicators.forEach(indicator => {
            indicator.remove();
        });
    }

    addShortcutIndicator(element, key, isInput = false) {
        const indicator = document.createElement('div');
        indicator.className = 'shortcut-indicator';
        indicator.innerHTML = `
            <span class="shortcut-key">
                ${this.modifierSymbol}${key.toUpperCase()}
            </span>
        `;

        // Position indicator based on element type
        if (isInput) {
            indicator.classList.add('shortcut-indicator-input');
            element.style.position = 'relative';
            element.appendChild(indicator);
        } else {
            indicator.classList.add('shortcut-indicator-button');
            element.style.position = 'relative';
            element.appendChild(indicator);
        }
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    new ShortcutIndicators();
});

// Also initialize on Livewire navigation (for SPA-like behavior)
document.addEventListener('livewire:navigated', () => {
    new ShortcutIndicators();
});

// Export for potential external use
window.ShortcutIndicators = ShortcutIndicators;
