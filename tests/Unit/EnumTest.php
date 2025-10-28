<?php

use App\Enums\ExpenseCategory;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentType;
use App\Enums\ProductDefault;

test('payment type enum has correct cases and methods', function () {
    $cases = PaymentType::cases();

    expect($cases)->toHaveCount(3);
    expect(PaymentType::Cash->getLabel())->toBe('Cash');
    expect(PaymentType::Transfer->getLabel())->toBe('Bank Transfer');
    expect(PaymentType::POS->getLabel())->toBe('POS Terminal');

    expect(PaymentType::Cash->getColor())->toBe('success');
    expect(PaymentType::Transfer->getColor())->toBe('info');
    expect(PaymentType::POS->getColor())->toBe('warning');

    expect(PaymentType::getDefault())->toBe(PaymentType::Cash);

    $options = PaymentType::getOptions();
    expect($options)->toHaveKey('cash', 'Cash');
    expect($options)->toHaveKey('transfer', 'Bank Transfer');
    expect($options)->toHaveKey('pos', 'POS Terminal');
});

test('invoice status enum has correct cases and business logic', function () {
    $cases = InvoiceStatus::cases();

    expect($cases)->toHaveCount(3);
    expect(InvoiceStatus::Unpaid->getLabel())->toBe('Unpaid');
    expect(InvoiceStatus::Partial->getLabel())->toBe('Partially Paid');
    expect(InvoiceStatus::Paid->getLabel())->toBe('Paid');

    expect(InvoiceStatus::Unpaid->getColor())->toBe('danger');
    expect(InvoiceStatus::Partial->getColor())->toBe('warning');
    expect(InvoiceStatus::Paid->getColor())->toBe('success');

    // Test status calculation logic
    expect(InvoiceStatus::calculateStatus(10000, 0))->toBe(InvoiceStatus::Unpaid);
    expect(InvoiceStatus::calculateStatus(10000, 5000))->toBe(InvoiceStatus::Partial);
    expect(InvoiceStatus::calculateStatus(10000, 10000))->toBe(InvoiceStatus::Paid);
    expect(InvoiceStatus::calculateStatus(10000, 15000))->toBe(InvoiceStatus::Paid);
});

test('expense category enum has correct cases and methods', function () {
    $cases = ExpenseCategory::cases();

    expect($cases)->toHaveCount(6);
    expect(ExpenseCategory::Miscellaneous->getLabel())->toBe('Miscellaneous');
    expect(ExpenseCategory::Materials->getLabel())->toBe('Materials');
    expect(ExpenseCategory::Utilities->getLabel())->toBe('Utilities');
    expect(ExpenseCategory::Repairs->getLabel())->toBe('Repairs & Maintenance');
    expect(ExpenseCategory::Cleaning->getLabel())->toBe('Cleaning');
    expect(ExpenseCategory::Staff->getLabel())->toBe('Staff');

    expect(ExpenseCategory::getDefault())->toBe(ExpenseCategory::Miscellaneous);

    $options = ExpenseCategory::getOptions();
    expect($options)->toHaveKey('miscellaneous', 'Miscellaneous');
    expect($options)->toHaveKey('materials', 'Materials');
});

test('product default enum has correct cases and dimensions', function () {
    $cases = ProductDefault::cases();

    expect($cases)->toHaveCount(3);
    expect(ProductDefault::SAV->getLabel())->toBe('SAV');
    expect(ProductDefault::FLEX->getLabel())->toBe('FLEX');
    expect(ProductDefault::TRANSPARENT->getLabel())->toBe('Transparent');

    expect(ProductDefault::SAV->getDescription())->toBe('Self-Adhesive Vinyl');
    expect(ProductDefault::FLEX->getDescription())->toBe('Flexible Banner Material');
    expect(ProductDefault::TRANSPARENT->getDescription())->toBe('Transparent Vinyl');

    // Test default dimensions
    $savDimensions = ProductDefault::SAV->getDefaultDimensions();
    expect($savDimensions)->toHaveKey('width', 1.0);
    expect($savDimensions)->toHaveKey('height', 1.0);
    expect($savDimensions)->toHaveKey('unit', 'sqm');

    $flexDimensions = ProductDefault::FLEX->getDefaultDimensions();
    expect($flexDimensions)->toHaveKey('width', 2.0);
    expect($flexDimensions)->toHaveKey('height', 1.0);

    // Test typical prices
    expect(ProductDefault::SAV->getTypicalPrice())->toBe(150000); // ₦1,500
    expect(ProductDefault::FLEX->getTypicalPrice())->toBe(120000); // ₦1,200
    expect(ProductDefault::TRANSPARENT->getTypicalPrice())->toBe(200000); // ₦2,000
});

test('all enums are string-backed', function () {
    expect(PaymentType::Cash)->toBeInstanceOf(PaymentType::class);
    expect(PaymentType::Cash->value)->toBe('cash');

    expect(InvoiceStatus::Unpaid)->toBeInstanceOf(InvoiceStatus::class);
    expect(InvoiceStatus::Unpaid->value)->toBe('unpaid');

    expect(ExpenseCategory::Miscellaneous)->toBeInstanceOf(ExpenseCategory::class);
    expect(ExpenseCategory::Miscellaneous->value)->toBe('miscellaneous');

    expect(ProductDefault::SAV)->toBeInstanceOf(ProductDefault::class);
    expect(ProductDefault::SAV->value)->toBe('sav');
});
