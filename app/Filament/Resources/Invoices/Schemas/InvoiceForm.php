<?php

namespace App\Filament\Resources\Invoices\Schemas;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentType;
use App\Models\Customer;
use App\Models\Product;
use Closure;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Icetalker\FilamentTableRepeater\Forms\Components\TableRepeater;
use NumberFormatter;

class InvoiceForm
{
    /**
     * Request-level cache for Product model lookups
     * Prevents redundant Product::find() calls during form operations
     *
     * @var array<int, Product|null>
     */
    private static array $productCache = [];

    /**
     * Request-level cache for Customer model lookups
     * Prevents redundant Customer::find() calls during form operations
     *
     * @var array<int, Customer|null>
     */
    private static array $customerCache = [];

    /**
     * Request-level cache for customer outstanding due amounts
     * Prevents redundant aggregate queries for the same customer
     *
     * @var array<int, float>
     */
    private static array $customerOutstandingCache = [];

    /**
     * Get or cache a Product model instance for the current request
     *
     * Uses a static cache that persists for the duration of the request,
     * reducing redundant database queries when the same product is accessed
     * multiple times (common in invoice form with repeated calculations).
     *
     * @param int $productId
     * @return Product|null
     */
    private static function getProductCached(int $productId): ?Product
    {
        if (!isset(self::$productCache[$productId])) {
            self::$productCache[$productId] = Product::find($productId);
        }
        return self::$productCache[$productId];
    }

    /**
     * Get or cache a Customer model instance for the current request
     *
     * @param int $customerId
     * @return Customer|null
     */
    private static function getCustomerCached(int $customerId): ?Customer
    {
        if (!isset(self::$customerCache[$customerId])) {
            self::$customerCache[$customerId] = Customer::find($customerId);
        }
        return self::$customerCache[$customerId];
    }

    /**
     * Get or cache a customer's outstanding due amount for the current request
     *
     * @param int $customerId
     * @return float
     */
    private static function getCustomerOutstandingCached(int $customerId): float
    {
        if (!isset(self::$customerOutstandingCache[$customerId])) {
            $customer = self::getCustomerCached($customerId);
            if ($customer) {
                self::$customerOutstandingCache[$customerId] = $customer->invoices()
                    ->where('status', '!=', InvoiceStatus::Paid)
                    ->sum('due');
            } else {
                self::$customerOutstandingCache[$customerId] = 0;
            }
        }
        return self::$customerOutstandingCache[$customerId];
    }

    public static function getFormComponents(): array
    {
        $schema = Schema::make();

        return static::configure($schema)->getComponents();
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                Group::make()
                    ->schema([
                        Section::make('Invoice Details')
                            ->schema([

                                TextInput::make('code')
                                    ->label('Invoice Code')
                                    ->disabled()
                                    ->dehydrated()
                                    ->default(function () {
                                        try {
                                            return \App\Models\Invoice::generateNewCode();
                                        } catch (\Exception $e) {
                                            return 'AUTO-GENERATED';
                                        }
                                    })
                                    ->prefix('#'),

                                Select::make('customer_id')
                                    ->label('Customer')
                                    ->relationship('customer', 'name')
                                    ->searchable()
                                    ->native(false)
                                    ->preload()
                                    ->required()
                                    ->autofocus()
                                    ->selectablePlaceholder(false)
                                    ->extraAttributes([
                                        'x-init' => '$nextTick(() => {
                                            setTimeout(() => {
                                                const input = $el.querySelector(\'.fi-select-input-placeholder\');
                                                if (input) {
                                                    input.click();
                                                    input.focus();
                                                }
                                            }, 100);
                                        })',
                                    ])
                                    ->createOptionForm([
                                        TextInput::make('name')
                                            ->required()
                                            ->maxLength(255),
                                        TextInput::make('email')
                                            ->email()
                                            ->maxLength(255),
                                        TextInput::make('phone')
                                            ->tel()
                                            ->maxLength(255),
                                        Textarea::make('address')
                                            ->maxLength(500),
                                    ])
                                    ->live()
                                    ->helperText(function (callable $get) {
                                        $customerId = $get('customer_id');
                                        if (! $customerId) {
                                            return null;
                                        }

                                        $customer = self::getCustomerCached($customerId);
                                        if (! $customer) {
                                            return null;
                                        }

                                        $totalDue = self::getCustomerOutstandingCached($customerId);

                                        if ($totalDue > 0) {
                                            return new \Illuminate\Support\HtmlString(
                                                'Outstanding: <span style="color: #dc2626; font-weight: 600;"> ₦'.
                                                number_format($totalDue / 100, 0).'</span>'
                                            );
                                        }

                                        return new \Illuminate\Support\HtmlString(
                                            '<span style="color: #16a34a; font-weight: 600;">No outstanding</span>'
                                        );
                                    })
                                    ->afterStateUpdated(function ($state, callable $set, callable $get, $livewire) {
                                        if ($state) {
                                            // Use cached customer and outstanding amount
                                            $customer = self::getCustomerCached($state);
                                            if ($customer) {
                                                // Calculate previous due amount (uses cache)
                                                $previousDue = self::getCustomerOutstandingCached($state);
                                            }
                                            // Dispatch browser event to focus date field
                                            $livewire->dispatch('focus-date-field');
                                        }
                                        self::updatePaymentTotals($set, $get);
                                    }),

                                DatePicker::make('date')
                                    ->label('Invoice Date')
                                    ->required()
                                    ->default(today())
                                    ->maxDate(today())
                                    ->native(true)
                                    ->extraAttributes([
                                        'x-on:focus-date-field.window' => 'setTimeout(() => { const input = $el.querySelector(\'input\'); if(input) { input.focus(); input.showPicker && input.showPicker(); } }, 100)',
                                    ]),

                                Textarea::make('note')
                                    ->label('Note')
                                    ->maxLength(500),

                            ]),

                    ])->columnSpan(['lg' => 1]),

                Group::make()
                    ->schema([

                        Section::make('Invoice Items')
                            ->schema([

                                View::make('filament.components.product-selector')
                                    ->viewData([
                                        'products' => Product::getCachedActive(),
                                    ]),

                                TableRepeater::make('products')
                                    ->label('')
                                    ->relationship()
                                    ->mutateRelationshipDataBeforeFillUsing(function (array $data): array {
                                        // MoneyCast already converts cents to dollars, no need to divide again

                                        // Load product name from product_id
                                        if (isset($data['product_id'])) {
                                            $productModel = self::getProductCached($data['product_id']);
                                            if ($productModel) {
                                                $data['product_name'] = $productModel->name;
                                            }
                                        }

                                        return $data;
                                    })
                                    ->mutateRelationshipDataBeforeSaveUsing(function (array $data): array {
                                        // MoneyCast will handle conversion to cents, no need to multiply

                                        // Remove display-only fields
                                        unset($data['product_name']);

                                        return $data;
                                    })
                                    ->default([])
                                    ->minItems(1)
                                    ->rules([
                                        function () {
                                            return function (string $attribute, $value, Closure $fail) {
                                                if (empty($value) || count($value) === 0) {
                                                    $fail('At least one product is required to create an invoice.');

                                                    Notification::make()
                                                        ->title('Products Required')
                                                        ->body('Please add at least one product to the invoice before saving.')
                                                        ->warning()
                                                        ->send();
                                                }
                                            };
                                        },
                                    ])
                                    ->extraAttributes([
                                        'class' => 'products-table',
                                    ])
                                    ->schema([

                                        Hidden::make('product_id')
                                            ->required(),

                                        TextInput::make('product_name')
                                            ->disabled()
                                            ->dehydrated(false)
                                            ->extraInputAttributes([
                                                'class' => 'min-w-[140px] md:min-w-[180px]',
                                            ]),

                                        TextInput::make('width')
                                            ->required()
                                            ->live(debounce: 800)
                                            ->inputMode('decimal')
                                            ->rules(['required', 'numeric', 'min:0.01', 'regex:/^\d+(\.\d{1,2})?$/'])
                                            ->validationMessages([
                                                'min' => 'Width must be greater than 0.',
                                                'numeric' => 'Width must be a valid number.',
                                                'regex' => 'Width must be a valid decimal number.',
                                            ])
                                            ->afterStateUpdated(function (callable $set, callable $get) {
                                                self::updateLineTotal($set, $get);
                                            })
                                            ->extraInputAttributes([
                                                'class' => 'text-right pr-0 sm:pr-3 md:pr-[25px] lg:pr-3 min-w-[80px] md:min-w-[100px]',
                                                'data-filter' => 'decimal',
                                            ])
                                            ->extraAttributes([
                                                'x-init' => '$nextTick(() => { setTimeout(() => { const input = $el.querySelector(\'input\'); if(input) { input.focus(); input.select(); console.log(\'✅ Width focused\'); } }, 300); })',
                                            ]),

                                        TextInput::make('height')
                                            ->required()
                                            ->live(debounce: 800)
                                            ->inputMode('decimal')
                                            ->rules(['required', 'numeric', 'min:0.01', 'regex:/^\d+(\.\d{1,2})?$/'])
                                            ->validationMessages([
                                                'min' => 'Height must be greater than 0.',
                                                'numeric' => 'Height must be a valid number.',
                                                'regex' => 'Height must be a valid decimal number.',
                                            ])
                                            ->afterStateUpdated(function (callable $set, callable $get) {
                                                self::updateLineTotal($set, $get);
                                            })
                                            ->extraInputAttributes([
                                                'class' => 'text-right pr-0 sm:pr-3 md:pr-[25px] lg:pr-3 min-w-[80px] md:min-w-[100px]',
                                                'data-filter' => 'decimal',
                                            ]),

                                        TextInput::make('unit_price')
                                            ->disabled()
                                            ->dehydrated()
                                            ->extraInputAttributes([
                                                'class' => 'text-right pr-0 sm:pr-3 md:pr-[25px] lg:pr-3 min-w-[90px] sm:min-w-[100px]',
                                            ]),

                                        TextInput::make('quantity')
                                            ->required()
                                            ->default(1)
                                            ->live(debounce: 800)
                                            ->inputMode('numeric')
                                            ->rules(['required', 'integer', 'min:1', 'regex:/^\d+$/'])
                                            ->validationMessages([
                                                'min' => 'Quantity must be at least 1.',
                                                'integer' => 'Quantity must be a whole number.',
                                                'regex' => 'Quantity must contain only numbers.',
                                            ])
                                            ->afterStateUpdated(function (callable $set, callable $get) {
                                                self::updateLineTotal($set, $get);
                                            })
                                            ->extraInputAttributes([
                                                'class' => 'text-right pr-0 sm:pr-3 md:pr-[25px] lg:pr-3 min-w-[80px] md:min-w-[100px]',
                                                'data-filter' => 'integer',
                                            ]),

                                        TextInput::make('product_amount')
                                            ->disabled()
                                            ->dehydrated()
                                            ->live()
                                            ->afterStateUpdated(function (callable $set, callable $get) {
                                                self::updateTotals($set, $get);
                                            })
                                            ->extraInputAttributes([
                                                'class' => 'text-right pr-0 sm:pr-3 md:pr-[25px] lg:pr-3 min-w-[100px] sm:min-w-[120px]',
                                            ]),

                                    ])
                                    ->deletable()
                                    ->addable(false)
                                    ->live()
                                    ->afterStateUpdated(function (callable $set, callable $get) {
                                        self::updateTotals($set, $get);
                                    })
                                    ->reorderable(false)
                                    ->reorderableWithButtons()
                                    ->reorderableWithDragAndDrop(false),


                            ])
                            ->extraAttributes(['class' => 'product-section']),

                        Group::make()
                            ->schema([
                                Group::make()
                                    ->schema([
                                        Section::make('')
                                            ->schema([
                                                TableRepeater::make('payments')
                                                    ->label('')
                                                    ->relationship()
                                                    ->mutateRelationshipDataBeforeFillUsing(function (array $data): array {
                                                        // MoneyCast already converts cents to dollars, no need to divide again
                                                        return $data;
                                                    })
                                                    ->mutateRelationshipDataBeforeSaveUsing(function (array $data): array {
                                                        // MoneyCast will handle conversion to cents, no need to multiply
                                                        return $data;
                                                    })
                                                    ->default([])
                                                    ->minItems(0)
                                                    ->extraAttributes([
                                                        'class' => 'payments-table',
                                                    ])
                                                    ->schema([
                                                        DatePicker::make('payment_date')
                                                            ->required()
                                                            ->default(function (callable $get) {
                                                                // Use invoice date if available, otherwise today
                                                                return $get('../../date') ?: today();
                                                            })
                                                            ->maxDate(today())
                                                            ->disabled(function (callable $get, $livewire) {
                                                                // Make readonly if this is an existing payment in edit mode
                                                                return $get('id') && method_exists($livewire, 'getRecord') && $livewire->getRecord();
                                                            })
                                                            ->extraInputAttributes([
                                                                'class' => 'min-w-[100px] md:min-w-[100px]',
                                                            ]),

                                                        Select::make('payment_type')
                                                            ->options(PaymentType::getOptions())
                                                            ->selectablePlaceholder(false)
                                                            ->required()
                                                            ->default(function (callable $get) {
                                                                // Only set default for new payments (no ID)
                                                                return $get('id') ? '' : PaymentType::Transfer->value;
                                                            })
                                                            ->disabled(function (callable $get, $livewire) {
                                                                // Make readonly if this is an existing payment in edit mode
                                                                return $get('id') && method_exists($livewire, 'getRecord') && $livewire->getRecord();
                                                            })
                                                            ->dehydrated()
                                                            ->live()
                                                            ->extraAttributes([
                                                                'class' => 'min-w-[100px] md:min-w-[100px]',
                                                            ]),

                                                        TextInput::make('amount')
                                                            ->required()
                                                            ->live(debounce: 800)
                                                            ->inputMode('decimal')
                                                            ->rules(['required', 'numeric', 'min:0.01', 'regex:/^\d+(\.\d{1,2})?$/'])
                                                            ->validationMessages([
                                                                'min' => 'Amount must be greater than 0.',
                                                                'numeric' => 'Amount must be a valid number.',
                                                                'regex' => 'Amount must be a valid decimal number.',
                                                            ])
                                                            ->afterStateUpdated(function (callable $set, callable $get) {
                                                                self::updatePaymentTotals($set, $get);
                                                            })
                                                            ->extraInputAttributes([
                                                                'class' => 'text-right pr-0 sm:pr-3 md:pr-[25px] lg:pr-3 min-w-[80px] md:min-w-[100px]',
                                                                'data-filter' => 'decimal',
                                                            ])
                                                            ->extraAttributes([
                                                                'x-init' => '$nextTick(() => { setTimeout(() => { const input = $el.querySelector(\'input\'); if(input && !input.disabled) { input.focus(); input.select(); console.log(\'✅ Amount focused\'); } }, 300); })',
                                                            ])
                                                            ->disabled(function (callable $get, $livewire) {
                                                                // Make readonly if this is an existing payment in edit mode
                                                                return $get('id') && method_exists($livewire, 'getRecord') && $livewire->getRecord();
                                                            }),

                                                        TextInput::make('note')
                                                            ->label('Note')
                                                            ->extraInputAttributes([
                                                                'class' => 'text-sm min-w-[100px] md:min-w-[120px]',
                                                            ]),

                                                    ])
                                                    ->deletable()
                                                    ->addable()
                                                    ->live()
                                                    ->afterStateUpdated(function (callable $set, callable $get) {
                                                        self::updatePaymentTotals($set, $get);
                                                    })
                                                    ->reorderable(false)
                                                    ->reorderableWithButtons()
                                                    ->reorderableWithDragAndDrop(false)
                                                    ->compact()
                                                    ->rules([
                                                        function (callable $get) {
                                                            return function (string $attribute, $value, Closure $fail) use ($get) {
                                                                // Get form data
                                                                $customerId = $get('customer_id');
                                                                $invoiceTotal = self::parseNumeric($get('total') ?? 0);
                                                                $paymentsData = $value ?? [];

                                                                if (empty($customerId) || $invoiceTotal <= 0) {
                                                                    return; // Skip validation if no customer or total
                                                                }

                                                                $customer = self::getCustomerCached($customerId);
                                                                if (! $customer) {
                                                                    return; // Skip if customer not found
                                                                }

                                                                // Filter valid payments (those with amount > 0)
                                                                $validPayments = collect($paymentsData)->filter(function ($payment) {
                                                                    return ! empty($payment['amount']) && (float) $payment['amount'] > 0;
                                                                });

                                                                // Convert form values to cents (database format) for proper comparison
                                                                $invoiceTotalCents = $invoiceTotal * 100; // Convert dollars to cents
                                                                $totalPaidCents = $validPayments->sum(function ($payment) {
                                                                    return (float) ($payment['amount'] ?? 0) * 100; // Convert dollars to cents
                                                                });

                                                                // Check if total payments exceed the invoice total for ALL customers
                                                                if ($totalPaidCents > $invoiceTotalCents) {
                                                                    $excessAmount = $totalPaidCents - $invoiceTotalCents;
                                                                    $fail('Total payments (₦'.number_format($totalPaidCents / 100).') cannot exceed the invoice total (₦'.number_format($invoiceTotalCents / 100).'). Excess amount: ₦'.number_format($excessAmount / 100));

                                                                    Notification::make()
                                                                        ->title('Excess Payment')
                                                                        ->body('Total payments (₦'.number_format($totalPaidCents / 100).') cannot exceed the invoice total (₦'.number_format($invoiceTotalCents / 100).'). Excess amount: ₦'.number_format($excessAmount / 100))
                                                                        ->danger()
                                                                        ->send();

                                                                    return;
                                                                }

                                                                // Additional validation for walk-in customers - they must pay in full
                                                                if (method_exists($customer, 'isWalkin') && $customer->isWalkin()) {
                                                                    // Check if there's at least one payment
                                                                    if ($validPayments->isEmpty()) {
                                                                        $fail('Walk-in customers must have at least one payment. All walk-in invoices must be fully paid.');

                                                                        Notification::make()
                                                                            ->title('Payment Required')
                                                                            ->body('Walk-in customers must have at least one payment. All walk-in invoices must be fully paid.')
                                                                            ->warning()
                                                                            ->send();

                                                                        return;
                                                                    }

                                                                    // Check if total payments equal the invoice total (allow 1 cent difference for rounding)
                                                                    if (abs($totalPaidCents - $invoiceTotalCents) > 1) {
                                                                        $fail('Walk-in customers must pay in full. Total payments must equal the grand total amount of ₦'.number_format($invoiceTotalCents / 100));

                                                                        Notification::make()
                                                                            ->title('Insufficient Payment')
                                                                            ->body('Walk-in customers must pay in full. Total payments must equal the grand total amount of ₦'.number_format($invoiceTotalCents / 100))
                                                                            ->warning()
                                                                            ->send();

                                                                        return;
                                                                    }
                                                                }
                                                            };
                                                        },
                                                    ]),

                                                // Payment Summary Section
                                                Group::make([
                                                    TextInput::make('total_payments_display')
                                                        ->label('Total Payments')
                                                        ->disabled()
                                                        ->dehydrated(false)
                                                        ->prefix('₦')
                                                        ->default('0')
                                                        ->extraInputAttributes(['class' => 'text-right font-medium']),

                                                    TextInput::make('outstanding_due_display')
                                                        ->label('Invoice Due')
                                                        ->disabled()
                                                        ->dehydrated(false)
                                                        ->prefix('₦')
                                                        ->default('0')
                                                        ->extraInputAttributes(['class' => 'text-right font-medium text-red-600 dark:text-red-400']),
                                                ])
                                                    ->columns(2)
                                                    ->extraAttributes(['class' => 'mt-4 p-3 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg']),
                                            ]),
                                    ])
                                    ->columnSpan(['lg' => 2]),

                                Group::make()
                                    ->schema([
                                        Section::make('')
                                            ->schema([
                                                TextInput::make('subtotal')
                                                    ->inlineLabel('Subtotal')
                                                    ->disabled()
                                                    ->dehydrated()
                                                    ->prefix('₦')
                                                    ->extraInputAttributes(['class' => 'text-right']),

                                                TextInput::make('discount')
                                                    ->inlineLabel('Discount')
                                                    ->prefix('₦')
                                                    ->default(0)
                                                    ->live(debounce: 800)
                                                    ->inputMode('decimal')
                                                    ->rules(['nullable', 'numeric', 'min:0', 'regex:/^\d+(\.\d{1,2})?$/'])
                                                    ->validationMessages([
                                                        'min' => 'Discount must be 0 or greater.',
                                                        'numeric' => 'Discount must be a valid number.',
                                                        'regex' => 'Discount must be a valid decimal number.',
                                                    ])
                                                    ->afterStateUpdated(function (callable $set, callable $get) {
                                                        self::updateTotals($set, $get);
                                                    })
                                                    ->extraInputAttributes([
                                                        'class' => 'text-right',
                                                        'data-filter' => 'decimal',
                                                    ]),

                                                TextInput::make('round_off')
                                                    ->inlineLabel('Round Off')
                                                    ->disabled()
                                                    ->dehydrated()
                                                    ->prefix('₦')
                                                    ->default(0)
                                                    ->extraInputAttributes(['class' => 'text-right']),

                                                TextInput::make('total')
                                                    ->inlineLabel('Grand Total')
                                                    ->disabled()
                                                    ->dehydrated()
                                                    ->prefix('₦')
                                                    ->extraInputAttributes(['class' => 'text-right font-bold']),

                                                Textarea::make('total_in_words')
                                                    ->label('')
                                                    ->hiddenLabel(true)
                                                    ->disabled()
                                                    ->dehydrated(false)
                                                    ->placeholder('Amount in words')
                                                    ->autosize()
                                                    ->rows(1)
                                                    ->extraAttributes(['class' => 'text-sm italic']),

                                                Hidden::make('paid')
                                                    ->default(0),

                                                Hidden::make('due')
                                                    ->default(0),

                                                Hidden::make('status')
                                                    ->default(InvoiceStatus::Unpaid->value),
                                            ])
                                            ->columns(1)
                                            ->columnSpan(['lg' => 1]),
                                    ])
                                    ->columnSpan(['lg' => 1]),
                            ])
                            ->columns(['lg' => 3]),

                    ])->columnSpan(['lg' => 3]),

            ])->columns(['lg' => 4]);
    }

    /**
     * Calculate line total for a specific product row
     */
    public static function updateLineTotal(callable $set, callable $get): void
    {
        $width = (float) ($get('width') ?? 0);
        $height = (float) ($get('height') ?? 0);
        $unitPrice = (float) ($get('unit_price') ?? 0);
        $quantity = (int) ($get('quantity') ?? 1);

        $amount = $width * $height * $unitPrice * $quantity;
        $set('product_amount', number_format($amount, 0, '.', ''));

        // Trigger totals update by getting the parent form state
        $products = $get('../../products') ?? [];
        $parentGet = function ($key) use ($get) {
            return $get('../../'.$key);
        };
        $parentSet = function ($key, $value) use ($set) {
            return $set('../../'.$key, $value);
        };
        self::updateTotals($parentSet, $parentGet);
    }

    /**
     * Calculate and update invoice totals
     */
    public static function updateTotals(callable $set, callable $get): void
    {
        $products = $get('products') ?? [];
        $discount = (float) ($get('discount') ?? 0);

        // Calculate subtotal from all products
        $subtotal = 0;
        foreach ($products as $product) {
            $subtotal += (float) ($product['product_amount'] ?? 0);
        }

        // Apply discount
        $afterDiscount = $subtotal - $discount;

        // Apply product minimum adjustments
        $finalAmount = $afterDiscount;
        foreach ($products as $product) {
            if (! empty($product['product_id'])) {
                $productModel = self::getProductCached($product['product_id']);
                if ($productModel && $productModel->minimum_amount > 0) {
                    $lineAmount = (float) ($product['product_amount'] ?? 0);
                    if ($lineAmount < $productModel->minimum_amount) {
                        $adjustment = $productModel->minimum_amount - $lineAmount;
                        $finalAmount += $adjustment;
                    }
                }
            }
        }

        // Round off to nearest 100 (10000 cents)
        $roundedAmount = round($finalAmount / 100) * 100;
        $roundOff = $roundedAmount - $finalAmount;

        // Set values (no comma formatting to avoid issues with MoneyCast)
        $set('subtotal', number_format($subtotal, 0, '.', ''));
        $set('round_off', number_format($roundOff, 0, '.', ''));
        $set('total', number_format($roundedAmount, 0, '.', ''));

        // Convert amount to words
        $formatter = new NumberFormatter('en_NG', NumberFormatter::SPELLOUT);
        $amountInWords = $formatter->format($roundedAmount);
        $totalInWords = ucwords($amountInWords).' Naira only';
        $set('total_in_words', $totalInWords);

        // Update payment totals
        self::updatePaymentTotals($set, $get);
    }

    /**
     * Calculate payment totals and update invoice status
     */
    public static function updatePaymentTotals(callable $set, callable $get): void
    {
        $payments = $get('payments') ?? [];
        $total = (float) self::parseNumeric($get('total') ?? 0);

        // Calculate total payments
        $totalPaid = 0;
        foreach ($payments as $payment) {
            if (! empty($payment['amount'])) {
                $totalPaid += (float) $payment['amount'];
            }
        }

        $due = max(0, $total - $totalPaid);

        // Determine status
        $status = InvoiceStatus::Unpaid;
        if ($totalPaid > 0) {
            $status = $due > 0.01 ? InvoiceStatus::Partial : InvoiceStatus::Paid;
        }

        // Set calculated values
        $set('paid', number_format($totalPaid, 0, '.', ''));
        $set('due', number_format($due, 0, '.', ''));
        $set('status', $status->value);

        // Update display fields
        $set('total_payments_display', number_format($totalPaid, 0));
        $set('outstanding_due_display', number_format($due, 0));
    }

    /**
     * Transform data before filling relationships
     */
    public static function mutateRelationshipDataBeforeFill(array $data): array
    {
        // Convert cents to dollars for display in forms
        if (isset($data['products'])) {
            foreach ($data['products'] as &$product) {
                if (isset($product['unit_price'])) {
                    $product['unit_price'] = $product['unit_price'] / 100;
                }
                if (isset($product['product_amount'])) {
                    $product['product_amount'] = $product['product_amount'] / 100;
                }

                // Always load product name from product_id for display
                if (isset($product['product_id'])) {
                    $productModel = self::getProductCached($product['product_id']);
                    if ($productModel) {
                        $product['product_name'] = $productModel->name;
                    }
                }
            }
        }

        if (isset($data['payments'])) {
            foreach ($data['payments'] as &$payment) {
                if (isset($payment['amount'])) {
                    $payment['amount'] = $payment['amount'] / 100;
                }
            }
        }

        return $data;
    }

    /**
     * Transform data before saving relationships
     */
    public static function mutateRelationshipDataBeforeSave(array $data): array
    {
        // Convert dollars to cents for database storage
        if (isset($data['products'])) {
            foreach ($data['products'] as &$product) {
                if (isset($product['unit_price'])) {
                    $product['unit_price'] = round($product['unit_price'] * 100);
                }
                if (isset($product['product_amount'])) {
                    $product['product_amount'] = round($product['product_amount'] * 100);
                }

                // Remove display-only fields
                unset($product['product_name']);
            }
        }

        if (isset($data['payments'])) {
            foreach ($data['payments'] as &$payment) {
                if (isset($payment['amount'])) {
                    $payment['amount'] = round($payment['amount'] * 100);
                }
            }
        }

        return $data;
    }

    public static function parseNumeric($value): float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }

        // Remove common formatting characters
        $cleaned = str_replace([',', ' ', '$'], '', $value);

        return (float) $cleaned;
    }
}
