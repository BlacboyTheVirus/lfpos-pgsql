<?php

use App\Filament\Resources\Invoices\Schemas\InvoiceForm;
use App\Models\Product;

beforeEach(function () {
    (new ReflectionProperty(InvoiceForm::class, 'productCache'))->setValue(null, []);
});

/**
 * @param  array<int, array{product_id: int, product_amount: float|int}>  $products
 * @return array<string, mixed>
 */
function calculateInvoiceTotals(array $products, float|int $discount = 0): array
{
    $state = ['products' => $products, 'discount' => $discount, 'payments' => []];

    InvoiceForm::updateTotals(
        function (string $key, mixed $value) use (&$state): void {
            $state[$key] = $value;
        },
        fn (string $key): mixed => $state[$key] ?? null,
    );

    return $state;
}

function createProductWithMinimum(float|int $minimumAmount): Product
{
    $productCache = new ReflectionProperty(InvoiceForm::class, 'productCache');
    $cachedProducts = $productCache->getValue();

    $product = new Product(['price' => 100, 'minimum_amount' => $minimumAmount]);
    $product->id = count($cachedProducts) + 1;

    $productCache->setValue(null, $cachedProducts + [$product->id => $product]);

    return $product;
}

it('includes the minimum amount top-up in the round off', function () {
    $product = createProductWithMinimum(5000);

    $totals = calculateInvoiceTotals([
        ['product_id' => $product->id, 'product_amount' => 3000],
    ]);

    expect($totals['subtotal'])->toBe('3000')
        ->and($totals['round_off'])->toBe('2000')
        ->and($totals['total'])->toBe('5000');
});

it('combines the minimum top-up with rounding', function () {
    $product = createProductWithMinimum(5050);

    $totals = calculateInvoiceTotals([
        ['product_id' => $product->id, 'product_amount' => 3000],
    ]);

    expect($totals['round_off'])->toBe('2100')
        ->and($totals['total'])->toBe('5100');
});

it('only rounds when the line is above the minimum', function () {
    $product = createProductWithMinimum(500);

    $totals = calculateInvoiceTotals([
        ['product_id' => $product->id, 'product_amount' => 3030],
    ]);

    expect($totals['round_off'])->toBe('-30')
        ->and($totals['total'])->toBe('3000');
});

it('keeps subtotal minus discount plus round off equal to the total', function () {
    $belowMinimum = createProductWithMinimum(5000);
    $aboveMinimum = createProductWithMinimum(500);

    $totals = calculateInvoiceTotals([
        ['product_id' => $belowMinimum->id, 'product_amount' => 1250],
        ['product_id' => $aboveMinimum->id, 'product_amount' => 2475],
    ], discount: 300);

    expect((float) $totals['subtotal'] - 300 + (float) $totals['round_off'])
        ->toBe((float) $totals['total'])
        ->and($totals['total'])->toBe('7200');
});

it('only rounds when the product has no minimum', function () {
    $product = createProductWithMinimum(0);

    $totals = calculateInvoiceTotals([
        ['product_id' => $product->id, 'product_amount' => 1260],
    ]);

    expect($totals['round_off'])->toBe('40')
        ->and($totals['total'])->toBe('1300');
});
