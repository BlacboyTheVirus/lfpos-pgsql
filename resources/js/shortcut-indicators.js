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
        this.addShortcutAttributesToSaveButtons();
        this.setupMutationObserver();
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

    addShortcutAttributesToSaveButtons() {
        // Find all Save buttons on the page and tag them with appropriate shortcuts
        const buttons = document.querySelectorAll('button, [role="button"]');

        buttons.forEach(button => {
            const text = button.textContent.trim().toLowerCase();

            // Don't add to disabled buttons or logout buttons
            if (button.disabled || this.isLogoutButton(button)) {
                return;
            }

            // Check if this is "Save & create another" button (Cmd+Shift+S)
            if (text.includes('save') && text.includes('create') && text.includes('another')) {
                button.setAttribute('data-shortcut', 'shift+s');
            }
            // Check if this is a regular Save button (Cmd+S)
            else if (text === 'save' || text.startsWith('save ')) {
                button.setAttribute('data-shortcut', 's');
            }
        });
    }

    setupMutationObserver() {
        // Watch for DOM changes and automatically tag new buttons
        const observer = new MutationObserver((mutations) => {
            let shouldUpdate = false;

            mutations.forEach((mutation) => {
                // Check if any buttons were added or modified
                if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                    mutation.addedNodes.forEach((node) => {
                        if (node.nodeType === 1) { // Element node
                            // Check if the added node is a button or contains buttons
                            if (node.matches && (node.matches('button') || node.matches('[role="button"]') ||
                                node.querySelector('button, [role="button"]'))) {
                                shouldUpdate = true;
                            }
                        }
                    });
                }
            });

            if (shouldUpdate) {
                // Debounce: wait a bit for multiple mutations
                clearTimeout(this.mutationTimeout);
                this.mutationTimeout = setTimeout(() => {
                    this.addShortcutAttributesToSaveButtons();
                    this.addShortcutAttributesToNavigation();
                }, 100);
            }
        });

        // Start observing the document body for changes
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });

        this.observer = observer;
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

    isLogoutButton(button) {
        if (!button) return false;

        const text = button.textContent.trim().toLowerCase();
        const excludedTexts = ['logout', 'sign out', 'signout', 'log out'];

        return excludedTexts.some(excluded => text.includes(excluded));
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

                // Handle Cmd+S or Cmd+Shift+S for Save buttons
                if (key === 's') {
                    e.preventDefault();
                    this.handleSaveShortcut(e.shiftKey);
                    return;
                }

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

    handleSaveShortcut(isShiftPressed = false) {
        let saveButton;

        if (isShiftPressed) {
            // Cmd+Shift+S: Find "Save & create another" button
            saveButton = document.querySelector('[data-shortcut="shift+s"]');

            if (!saveButton) {
                // Fallback: Look for buttons with "save" and "create" text
                const buttons = Array.from(document.querySelectorAll('button, [role="button"]'));
                saveButton = buttons.find(btn => {
                    if (this.isLogoutButton(btn)) return false;
                    const text = btn.textContent.trim().toLowerCase();
                    return text.includes('save') && text.includes('create') && text.includes('another');
                });
            }
        } else {
            // Cmd+S: Find primary Save button
            // Priority order:
            // 1. Button with data-shortcut="s"
            // 2. Button with text "Save" (case insensitive, exact match)
            // 3. Submit buttons in forms

            saveButton = document.querySelector('[data-shortcut="s"]');

            if (!saveButton) {
                // Look for buttons with exact "Save" text (not "Save & create another")
                const buttons = Array.from(document.querySelectorAll('button, [role="button"]'));
                saveButton = buttons.find(btn => {
                    if (this.isLogoutButton(btn)) return false;
                    const text = btn.textContent.trim().toLowerCase();
                    return text === 'save';
                });
            }

            if (!saveButton) {
                // Look for submit buttons in visible forms, but exclude logout buttons
                const submitButtons = Array.from(document.querySelectorAll('form button[type="submit"]'));
                saveButton = submitButtons.find(btn => !this.isLogoutButton(btn));
            }
        }

        // Final safety check: never click logout buttons
        if (saveButton && !saveButton.disabled && !this.isLogoutButton(saveButton)) {
            saveButton.click();
        }
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

        // Handle shift+key shortcuts (e.g., "shift+s" becomes "⌘⇧S")
        let displayText;
        if (key.startsWith('shift+')) {
            const actualKey = key.replace('shift+', '').toUpperCase();
            displayText = this.isMac
                ? `${this.modifierSymbol}⇧${actualKey}`
                : `Ctrl+Shift+${actualKey}`;
        } else {
            displayText = `${this.modifierSymbol}${key.toUpperCase()}`;
        }

        indicator.innerHTML = `
            <span class="shortcut-key">
                ${displayText}
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

// Store instance globally
let shortcutIndicatorsInstance = null;

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    shortcutIndicatorsInstance = new ShortcutIndicators();
});

// Also initialize on Livewire navigation (for SPA-like behavior)
document.addEventListener('livewire:navigated', () => {
    shortcutIndicatorsInstance = new ShortcutIndicators();
});

// Re-add shortcut attributes when Livewire updates the DOM
document.addEventListener('livewire:updated', () => {
    if (shortcutIndicatorsInstance) {
        shortcutIndicatorsInstance.addShortcutAttributesToSaveButtons();
        shortcutIndicatorsInstance.addShortcutAttributesToNavigation();
    }
});

// Export for potential external use
window.ShortcutIndicators = ShortcutIndicators;
