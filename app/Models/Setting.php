<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'value',
        'description',
        'is_json',
        'is_encrypted',
    ];

    public function getValueAttribute($value)
    {
        // Try to decode JSON, return original value if not JSON
        $decoded = json_decode($value, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
    }

    public function setValueAttribute($value): void
    {
        // Encode arrays and objects as JSON
        $this->attributes['value'] = is_array($value) || is_object($value)
            ? json_encode($value)
            : $value;
    }

    /**
     * Get a setting value by name with caching
     */
    public static function get(string $name, $default = null)
    {
        return Cache::remember("setting.{$name}", 3600, function () use ($name, $default) {
            return static::where('name', $name)->value('value') ?? $default;
        });
    }

    /**
     * Set a setting value by name and clear cache
     */
    public static function set(string $name, $value): void
    {
        static::updateOrCreate(
            ['name' => $name],
            ['value' => $value]
        );

        Cache::forget("setting.{$name}");
    }

    /**
     * Get multiple settings at once
     */
    public static function getMultiple(array $names, array $defaults = []): array
    {
        $settings = [];
        foreach ($names as $name) {
            $settings[$name] = static::get($name, $defaults[$name] ?? null);
        }

        return $settings;
    }

    /**
     * Set multiple settings at once
     */
    public static function setMultiple(array $settings): void
    {
        foreach ($settings as $name => $value) {
            static::set($name, $value);
        }
    }

    /**
     * Get company/business information settings
     */
    public static function getCompanyInfo(): array
    {
        return static::getMultiple([
            'company_name',
            'company_address',
            'company_phone',
            'company_email',
            'company_website',
            'company_logo',
        ], [
            'company_name' => 'Blacboy Kreative',
            'company_address' => '',
            'company_phone' => '',
            'company_email' => '',
            'company_website' => '',
            'company_logo' => null,
        ]);
    }

    /**
     * Get bank account information for invoices
     */
    public static function getBankInfo(): array
    {
        return static::getMultiple([
            'bank_name',
            'bank_account_name',
            'bank_account_number',
            'bank_sort_code',
        ], [
            'bank_name' => 'Palmpay',
            'bank_account_name' => 'BlacboyKrtv(Abisoye)',
            'bank_account_number' => '8900737563',
            'bank_sort_code' => '010',
        ]);
    }

    /**
     * Get currency and formatting settings
     */
    public static function getCurrencySettings(): array
    {
        return static::getMultiple([
            'currency_symbol',
            'currency_position',
            'decimal_places',
            'thousands_separator',
            'decimal_separator',
        ], [
            'currency_symbol' => '₦',
            'currency_position' => 'before',
            'decimal_places' => 0,
            'thousands_separator' => ',',
            'decimal_separator' => '.',
        ]);
    }

    /**
     * Get application preferences
     */
    public static function getAppPreferences(): array
    {
        return static::getMultiple([
            'app_timezone',
            'date_format',
            'time_format',
            'items_per_page',
            'auto_backup_enabled',
            'auto_backup_frequency',
        ], [
            'app_timezone' => 'Africa/Lagos',
            'date_format' => 'Y-m-d',
            'time_format' => 'H:i:s',
            'items_per_page' => 10,
            'auto_backup_enabled' => false,
            'auto_backup_frequency' => 'daily',
        ]);
    }

    /**
     * Get invoice-specific settings
     */
    public static function getInvoiceSettings(): array
    {
        return static::getMultiple([
            'invoice_terms',
            'invoice_footer',
            'auto_round_totals',
            'round_to_nearest',
            'show_bank_details',
            'invoice_due_days',
        ], [
            'invoice_terms' => 'Payment is due within 30 days of invoice date.',
            'invoice_footer' => 'Thank you for your business!',
            'auto_round_totals' => true,
            'round_to_nearest' => 100,
            'show_bank_details' => true,
            'invoice_due_days' => 30,
        ]);
    }

    /**
     * Format money according to currency settings
     */
    public static function formatMoney(int $amountInCents): string
    {
        $settings = static::getCurrencySettings();
        $amount = $amountInCents;

        $formatted = number_format(
            $amount,
            $settings['decimal_places'],
            $settings['decimal_separator'],
            $settings['thousands_separator']
        );

        return $settings['currency_position'] === 'before'
            ? $settings['currency_symbol'].$formatted
            : $formatted.$settings['currency_symbol'];
    }

    /**
     * Get code generation settings for all prefixes
     */
    public static function getCodeSettings(): array
    {
        return static::getMultiple([
            'customer_code_prefix',
            'customer_code_format',
            'product_code_prefix',
            'product_code_format',
            'invoice_code_prefix',
            'invoice_code_format',
            'expense_code_prefix',
            'expense_code_format',
        ], [
            'customer_code_prefix' => 'CU-',
            'customer_code_format' => '%04d',
            'product_code_prefix' => 'PR-',
            'product_code_format' => '%04d',
            'invoice_code_prefix' => 'IN-',
            'invoice_code_format' => '%05d',
            'expense_code_prefix' => 'EX-',
            'expense_code_format' => '%04d',
        ]);
    }

    /**
     * Get next available code for a given prefix
     */
    public static function getNextCode(string $prefix): string
    {
        $settingName = "next_code_{$prefix}";
        $prefixSettingName = "{$prefix}_code_prefix";
        $formatSettingName = "{$prefix}_code_format";

        $nextNumber = (int) static::get($settingName, 1);

        // Get configurable prefix and format from settings with proper defaults
        $defaultPrefix = match ($prefix) {
            'customer' => 'CU-',
            'product' => 'PR-',
            'invoice' => 'IN-',
            'expense' => 'EX-',
            default => strtoupper(substr($prefix, 0, 2)).'-',
        };

        $defaultFormat = match ($prefix) {
            'invoice' => '%05d',
            default => '%04d',
        };

        $codePrefix = static::get($prefixSettingName, $defaultPrefix);
        $codeFormat = static::get($formatSettingName, $defaultFormat);

        $code = $codePrefix.sprintf($codeFormat, $nextNumber);

        // Increment for next use
        static::set($settingName, $nextNumber + 1);

        return $code;
    }

    /**
     * Reset code counter for a given prefix
     */
    public static function resetCodeCounter(string $prefix, int $startFrom = 1): void
    {
        $settingName = "next_code_{$prefix}";
        static::set($settingName, $startFrom);
    }

    /**
     * Check if a feature is enabled
     */
    public static function isFeatureEnabled(string $feature): bool
    {
        return (bool) static::get("feature_{$feature}", false);
    }

    /**
     * Enable/disable a feature
     */
    public static function setFeature(string $feature, bool $enabled): void
    {
        static::set("feature_{$feature}", $enabled);
    }

    /**
     * Clear all settings cache
     */
    public static function clearCache(): void
    {
        $settings = static::all();
        foreach ($settings as $setting) {
            Cache::forget("setting.{$setting->name}");
        }
    }
}
