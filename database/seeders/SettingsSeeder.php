<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $currentSettings = [
            // Company Information
            'company_name' => 'Blacboy Kreative',
            'company_address' => 'No. 6 Cameroon Road, Kaduna, Nigeria.',
            'company_phone' => '+234 801 234 5678',
            'company_email' => 'info@blacboykreative.com',
            'company_website' => 'https://blacboykreative.com',
            'company_logo' => 'BBK_LFPOS',

            // Bank Account Information
            'bank_name' => 'Palmpay',
            'bank_account_name' => 'LfPos Business Account',
            'bank_account_number' => '8900786030',
            'bank_sort_code' => '011',

            // Currency Settings
            'currency_symbol' => '₦',
            'currency_position' => 'before',
            'decimal_places' => '0',

            // Application Preferences
            'date_format' => 'Y-m-d',
            'time_format' => 'H:i:s',

            // Invoice Settings
            'invoice_terms' => 'Payment is due within 30 days of invoice date. Late payments may incur additional charges.',
            'invoice_footer' => 'Thank you for choosing LfPos! We appreciate your business and look forward to serving you again.',
            'round_to_nearest' => '100',

            // Code Generation Starting Points
            'next_code_customer' => '1',
            'next_code_product' => '1',
            'next_code_invoice' => '1',
            'next_code_expense' => '1',

            // Code Prefixes
            'customer_code_prefix' => 'CU-',
            'product_code_prefix' => 'PR-',
            'invoice_code_prefix' => 'IN-',
            'expense_code_prefix' => 'EX-',

            // Code Formats
            'customer_code_format' => '%04d',
            'product_code_format' => '%04d',
            'invoice_code_format' => '%05d',
            'expense_code_format' => '%04d',

            // UI/UX Settings
            'default_dashboard_widgets' => '["total_sales_today","pending_invoices","low_stock_products","recent_customers","monthly_revenue_chart"]',
            'enable_dark_mode' => '0',
            'lazy_load_images' => '1',
            'paginate_large_tables' => '1',

            // Notification Settings
            'email_invoice_on_creation' => '0',

            // Product Settings
            'enable_product_variants' => '0',

            // System Maintenance
            'maintenance_mode' => '0',
            'maintenance_message' => 'System is currently under maintenance. Please try again later.',
            'last_backup_date' => null,
            'last_migration_date' => '2025-10-27 21:08:34',

            // Security Settings
            'session_timeout_minutes' => '120',
        ];

        // Insert settings using the model's set method to ensure proper caching
        foreach ($currentSettings as $name => $value) {
            Setting::set($name, $value);
        }

        $this->command->info('Settings seeded successfully with '.count($currentSettings).' current values.');
    }
}
