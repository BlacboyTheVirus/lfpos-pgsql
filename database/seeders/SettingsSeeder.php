<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $defaultSettings = [
            // Company Information
            'company_name' => 'LfPos Business',
            'company_address' => 'No. 123 Business Street, Lagos, Nigeria',
            'company_phone' => '+234 801 234 5678',
            'company_email' => 'info@lfpos.com',
            'company_website' => 'https://lfpos.com',
            'company_logo' => null,

            // Bank Account Information
            'bank_name' => 'First Bank of Nigeria',
            'bank_account_name' => 'LfPos Business Account',
            'bank_account_number' => '1234567890',
            'bank_sort_code' => '011',

            // Currency Settings
            'currency_symbol' => '₦',
            'currency_position' => 'before',
            'decimal_places' => 0,
            'thousands_separator' => ',',
            'decimal_separator' => '.',

            // Application Preferences
            'app_timezone' => 'Africa/Lagos',
            'date_format' => 'Y-m-d',
            'time_format' => 'H:i:s',
            'items_per_page' => 10,
            'auto_backup_enabled' => false,
            'auto_backup_frequency' => 'daily',

            // Invoice Settings
            'invoice_terms' => 'Payment is due within 30 days of invoice date. Late payments may incur additional charges.',
            'invoice_footer' => 'Thank you for choosing LfPos! We appreciate your business and look forward to serving you again.',
            'auto_round_totals' => true,
            'round_to_nearest' => 100,
            'show_bank_details' => true,
            'invoice_due_days' => 30,

            // Code Generation Starting Points
            'next_code_customer' => 1,
            'next_code_product' => 1,
            'next_code_invoice' => 1,
            'next_code_expense' => 1,

            // Code Prefixes (configurable)
            'customer_code_prefix' => 'CU-',
            'product_code_prefix' => 'PR-',
            'invoice_code_prefix' => 'IN-',
            'expense_code_prefix' => 'EX-',

            // Code Formats (configurable)
            'customer_code_format' => '%04d',
            'product_code_format' => '%04d',
            'invoice_code_format' => '%05d',
            'expense_code_format' => '%04d',

            // Feature Flags
            'feature_auto_backup' => false,
            'feature_email_notifications' => false,
            'feature_sms_notifications' => false,
            'feature_multi_currency' => false,
            'feature_inventory_tracking' => false,
            'feature_barcode_scanning' => false,
            'feature_customer_loyalty' => false,
            'feature_advanced_reporting' => true,
            'feature_api_access' => false,
            'feature_mobile_app' => false,

            // Business Rules
            'min_customer_credit_limit' => 0,
            'max_customer_credit_limit' => 100000000, // ₦1,000,000 in cents
            'default_payment_terms_days' => 30,
            'allow_negative_inventory' => false,
            'require_customer_for_invoice' => true,
            'auto_generate_invoice_numbers' => true,
            'allow_invoice_editing_after_payment' => false,

            // UI/UX Settings
            'default_dashboard_widgets' => json_encode([
                'total_sales_today',
                'pending_invoices',
                'low_stock_products',
                'recent_customers',
                'monthly_revenue_chart',
            ]),
            'enable_dark_mode' => false,
            'compact_sidebar' => false,
            'show_tooltips' => true,
            'auto_save_forms' => true,

            // Notification Settings
            'notify_low_stock_threshold' => 10,
            'notify_overdue_invoices' => true,
            'notify_payment_received' => true,
            'notify_new_customer' => false,
            'email_invoice_on_creation' => false,
            'sms_payment_reminders' => false,

            // Security Settings
            'session_timeout_minutes' => 120,
            'password_expires_days' => 90,
            'max_login_attempts' => 5,
            'lockout_duration_minutes' => 15,
            'require_two_factor' => false,
            'audit_log_retention_days' => 365,

            // Report Settings
            'default_report_date_range' => '30_days',
            'export_max_records' => 10000,
            'include_deleted_records_in_reports' => false,
            'watermark_exported_pdfs' => true,

            // Product Settings
            'default_product_unit' => 'sqm',
            'auto_calculate_product_margins' => true,
            'show_product_images' => true,
            'require_product_description' => false,
            'enable_product_variants' => false,

            // Customer Settings
            'auto_create_customer_codes' => true,
            'require_customer_phone' => true,
            'require_customer_email' => false,
            'allow_duplicate_customer_names' => false,
            'customer_credit_check_enabled' => true,

            // Invoice Line Item Settings
            'max_invoice_line_items' => 50,
            'default_quantity' => 1,
            'auto_focus_width_field' => true,
            'show_line_item_notes' => true,
            'calculate_tax_per_line_item' => false,

            // System Maintenance
            'maintenance_mode' => false,
            'maintenance_message' => 'System is currently under maintenance. Please try again later.',
            'last_backup_date' => null,
            'database_version' => '1.0.0',
            'last_migration_date' => now()->toDateTimeString(),

            // Performance Settings
            'cache_duration_hours' => 1,
            'enable_query_caching' => true,
            'compress_exports' => false,
            'lazy_load_images' => true,
            'paginate_large_tables' => true,
        ];

        // Insert settings using the model's set method to ensure proper caching
        foreach ($defaultSettings as $name => $value) {
            Setting::set($name, $value);
        }

        $this->command->info('Settings seeded successfully with '.count($defaultSettings).' default values.');
    }
}
