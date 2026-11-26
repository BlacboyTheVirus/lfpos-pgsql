<?php

use App\Filament\Resources\Invoices\Pages\CreateInvoice;
use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\assertDatabaseHas;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::findOrCreate('panel_user', 'web');
    Gate::before(fn (): bool => true);

    $this->actingAs(User::factory()->create());
});

test('product is created with the entered default dimensions', function () {
    Livewire::test(CreateProduct::class)
        ->fillForm([
            'name' => 'Flex Banner',
            'unit' => 'sqft',
            'price' => 1200,
            'minimum_amount' => 0,
            'default_width' => 2.5,
            'default_height' => 3,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    assertDatabaseHas(Product::class, [
        'name' => 'Flex Banner',
        'default_width' => 2.5,
        'default_height' => 3,
    ]);
});

test('default dimensions fall back to 1 x 1', function () {
    Livewire::test(CreateProduct::class)
        ->assertSchemaStateSet([
            'default_width' => 1,
            'default_height' => 1,
        ]);

    $product = Product::create(['name' => 'SAV', 'unit' => 'sqft', 'price' => 1500]);

    expect($product->fresh())
        ->default_width->toBe('1.00')
        ->default_height->toBe('1.00');
});

test('default dimensions are validated', function (string $field, mixed $value, string $rule) {
    Livewire::test(CreateProduct::class)
        ->fillForm([
            'name' => 'Transparent',
            'unit' => 'sqft',
            'price' => 2000,
            'minimum_amount' => 0,
            'default_width' => 1,
            'default_height' => 1,
            $field => $value,
        ])
        ->call('create')
        ->assertHasFormErrors([$field => $rule]);
})->with([
    'width zero' => ['default_width', 0, 'min'],
    'height zero' => ['default_height', 0, 'min'],
    'width missing' => ['default_width', null, 'required'],
    'height missing' => ['default_height', null, 'required'],
    'height not numeric' => ['default_height', 'abc', 'numeric'],
]);

test('default dimensions can be updated', function () {
    $product = Product::create(['name' => 'SAV', 'unit' => 'sqft', 'price' => 1500]);

    Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
        ->fillForm(['default_width' => 4, 'default_height' => 2.25])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($product->fresh())
        ->default_width->toBe('4.00')
        ->default_height->toBe('2.25');
});

test('adding a product to an invoice pre-fills its default width and height', function () {
    $product = Product::create(['name' => 'Flex', 'unit' => 'sqft', 'price' => 100]);

    $products = Livewire::test(CreateInvoice::class)
        ->call('addProductToForm', $product->id, 'Flex', 100, 2.5, 3, 0)
        ->get('data.products');

    $line = collect($products)->last();

    expect($line)
        ->width->toEqual(2.5)
        ->height->toEqual(3)
        ->product_amount->toEqual('750');
});
