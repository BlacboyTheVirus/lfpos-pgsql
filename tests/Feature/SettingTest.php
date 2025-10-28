<?php

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('setting can store and retrieve values', function () {
    Setting::set('test_key', 'test_value');

    expect(Setting::get('test_key'))->toBe('test_value');
});

test('setting returns default value when key does not exist', function () {
    expect(Setting::get('nonexistent_key_123', 'default'))->toBe('default');
    expect(Setting::get('nonexistent_key_456'))->toBeNull();
});

test('setting can store and retrieve json data', function () {
    $data = ['key1' => 'value1', 'key2' => 'value2'];

    Setting::set('json_data', $data);

    expect(Setting::get('json_data'))->toBe($data);
});

test('setting generates configurable codes correctly', function () {
    // Test with default settings
    $customerCode1 = Setting::getNextCode('customer');
    $customerCode2 = Setting::getNextCode('customer');

    expect($customerCode1)->toBe('CU-0001');
    expect($customerCode2)->toBe('CU-0002');

    // Test with custom prefix and format
    Setting::set('product_code_prefix', 'PROD-');
    Setting::set('product_code_format', '%05d');

    $productCode1 = Setting::getNextCode('product');
    expect($productCode1)->toBe('PROD-00001');

    // Test invoice default format (5 digits)
    $invoiceCode1 = Setting::getNextCode('invoice');
    expect($invoiceCode1)->toBe('IN-00001');
});

test('setting formats money correctly', function () {
    // Test default Nigerian Naira formatting
    expect(Setting::formatMoney(150000))->toBe('₦1,500');
    expect(Setting::formatMoney(250000))->toBe('₦2,500');
    expect(Setting::formatMoney(1000000))->toBe('₦10,000');
});

test('setting provides company info with defaults', function () {
    $companyInfo = Setting::getCompanyInfo();

    expect($companyInfo)->toHaveKey('company_name');
    expect($companyInfo)->toHaveKey('company_address');
    expect($companyInfo)->toHaveKey('company_phone');
    expect($companyInfo)->toHaveKey('company_email');
    expect($companyInfo)->toHaveKey('company_website');
    expect($companyInfo)->toHaveKey('company_logo');
});

test('setting provides bank info with defaults', function () {
    $bankInfo = Setting::getBankInfo();

    expect($bankInfo)->toHaveKey('bank_name');
    expect($bankInfo)->toHaveKey('bank_account_name');
    expect($bankInfo)->toHaveKey('bank_account_number');
    expect($bankInfo)->toHaveKey('bank_sort_code');
});

test('setting can manage feature flags', function () {
    expect(Setting::isFeatureEnabled('test_feature'))->toBeFalse();

    Setting::setFeature('test_feature', true);
    expect(Setting::isFeatureEnabled('test_feature'))->toBeTrue();

    Setting::setFeature('test_feature', false);
    expect(Setting::isFeatureEnabled('test_feature'))->toBeFalse();
});
