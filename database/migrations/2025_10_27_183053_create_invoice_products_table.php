<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('invoice_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained();
            $table->decimal('width', 8, 2)->default(1);
            $table->decimal('height', 8, 2)->default(1);
            $table->integer('unit_price'); // Amount in cents
            $table->integer('quantity')->default(1);
            $table->integer('product_amount'); // Amount in cents (width * height * unit_price * quantity)
            $table->timestamps();

            $table->index(['invoice_id', 'product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_products');
    }
};
