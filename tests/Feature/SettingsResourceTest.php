<?php

use App\Filament\Resources\Settings\Pages\CreateSetting;
use App\Filament\Resources\Settings\Pages\EditSetting;
use App\Filament\Resources\Settings\Pages\ListSettings;
use App\Filament\Resources\Settings\Pages\ViewSetting;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('can render settings list page', function () {
    Livewire::test(ListSettings::class)
        ->assertSuccessful();
});

test('can list settings', function () {
    $settings = Setting::factory()->count(3)->create();

    Livewire::test(ListSettings::class)
        ->assertCanSeeTableRecords($settings);
});

test('can search settings by name', function () {
    $settings = Setting::factory()->count(3)->create();
    $targetSetting = $settings->first();

    Livewire::test(ListSettings::class)
        ->searchTable($targetSetting->name)
        ->assertCanSeeTableRecords([$targetSetting])
        ->assertCanNotSeeTableRecords($settings->skip(1));
});

test('can filter settings by category', function () {
    Setting::factory()->create(['name' => 'company_name', 'value' => 'Test Company']);
    Setting::factory()->create(['name' => 'bank_name', 'value' => 'Test Bank']);
    Setting::factory()->create(['name' => 'other_setting', 'value' => 'Other Value']);

    Livewire::test(ListSettings::class)
        ->filterTable('category', 'company')
        ->assertTableRowsCount(1);
});

test('can create setting', function () {
    $newData = [
        'name' => 'test_setting',
        'value' => 'test_value',
        'description' => 'Test setting description',
    ];

    Livewire::test(CreateSetting::class)
        ->fillForm($newData)
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas(Setting::class, $newData);
});

test('can create setting with json value', function () {
    $jsonData = ['key' => 'value', 'nested' => ['data' => 'test']];
    $newData = [
        'name' => 'json_setting',
        'value' => json_encode($jsonData),
        'is_json' => true,
    ];

    Livewire::test(CreateSetting::class)
        ->fillForm($newData)
        ->call('create')
        ->assertHasNoFormErrors();

    $setting = Setting::where('name', 'json_setting')->first();
    expect($setting)->not->toBeNull();
    expect($setting->value)->toBe($jsonData);
});

test('validates required fields when creating setting', function () {
    Livewire::test(CreateSetting::class)
        ->fillForm([])
        ->call('create')
        ->assertHasFormErrors(['name', 'value']);
});

test('validates unique setting name', function () {
    $existingSetting = Setting::factory()->create();

    Livewire::test(CreateSetting::class)
        ->fillForm([
            'name' => $existingSetting->name,
            'value' => 'test_value',
        ])
        ->call('create')
        ->assertHasFormErrors(['name']);
});

test('can edit setting', function () {
    $setting = Setting::factory()->create();
    $newData = [
        'name' => $setting->name,
        'value' => 'updated_value',
        'description' => 'Updated description',
    ];

    Livewire::test(EditSetting::class, ['record' => $setting->getRouteKey()])
        ->fillForm($newData)
        ->call('save')
        ->assertHasNoFormErrors();

    expect($setting->refresh()->value)->toBe('updated_value');
});

test('can view setting', function () {
    $setting = Setting::factory()->create();

    Livewire::test(ViewSetting::class, ['record' => $setting->getRouteKey()])
        ->assertSuccessful()
        ->assertSeeText($setting->name)
        ->assertSeeText($setting->value);
});

test('can delete setting from table', function () {
    $setting = Setting::factory()->create(['name' => 'deletable_setting']);

    Livewire::test(ListSettings::class)
        ->callTableAction('delete', $setting)
        ->assertNotified();

    $this->assertDatabaseMissing(Setting::class, ['id' => $setting->id]);
});

test('cannot delete protected setting', function () {
    $protectedSetting = Setting::factory()->create(['name' => 'currency_symbol']);

    Livewire::test(ListSettings::class)
        ->assertTableActionNotVisible('delete', $protectedSetting);
});

test('can bulk delete settings', function () {
    $settings = Setting::factory()->count(3)->create([
        'name' => fn () => 'deletable_'.fake()->word(),
    ]);

    Livewire::test(ListSettings::class)
        ->callTableBulkAction('delete', $settings)
        ->assertNotified();

    foreach ($settings as $setting) {
        $this->assertDatabaseMissing(Setting::class, ['id' => $setting->id]);
    }
});

test('displays correct category badge for settings', function () {
    $companySetting = Setting::factory()->create(['name' => 'company_name']);
    $bankSetting = Setting::factory()->create(['name' => 'bank_name']);

    Livewire::test(ListSettings::class)
        ->assertTableColumnFormattedStateSet('category', 'Company', $companySetting)
        ->assertTableColumnFormattedStateSet('category', 'Banking', $bankSetting);
});
