// Numeric input filter for invoice form fields
document.addEventListener('DOMContentLoaded', function() {
    // Function to filter decimal inputs (allows numbers and one decimal point, max 2 decimal places)
    function filterDecimalInput(input) {
        input.addEventListener('input', function(e) {
            let value = e.target.value;
            // Remove any non-numeric characters except decimal point
            value = value.replace(/[^\d.]/g, '');
            // Allow only one decimal point
            const parts = value.split('.');
            if (parts.length > 2) {
                value = parts[0] + '.' + parts.slice(1).join('');
            }
            // Limit to 2 decimal places
            if (parts.length === 2 && parts[1].length > 2) {
                value = parts[0] + '.' + parts[1].substring(0, 2);
            }
            if (e.target.value !== value) {
                e.target.value = value;
            }
        });
    }

    // Function to filter integer inputs (allows only whole numbers)
    function filterIntegerInput(input) {
        input.addEventListener('input', function(e) {
            let value = e.target.value;
            // Remove any non-numeric characters
            value = value.replace(/[^\d]/g, '');
            if (e.target.value !== value) {
                e.target.value = value;
            }
        });
    }

    // Apply filters to form fields using data attributes
    function applyFilters() {
        // Filter decimal inputs
        document.querySelectorAll('input[data-filter="decimal"]').forEach(input => {
            if (!input.dataset.filterApplied) {
                filterDecimalInput(input);
                input.dataset.filterApplied = 'true';
            }
        });

        // Filter integer inputs
        document.querySelectorAll('input[data-filter="integer"]').forEach(input => {
            if (!input.dataset.filterApplied) {
                filterIntegerInput(input);
                input.dataset.filterApplied = 'true';
            }
        });
    }

    // Initial application
    applyFilters();

    // Re-apply when Livewire updates the DOM
    document.addEventListener('livewire:navigated', applyFilters);
    document.addEventListener('livewire:load', applyFilters);

    // Use MutationObserver to detect dynamically added fields
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.addedNodes.length) {
                applyFilters();
            }
        });
    });

    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
});
