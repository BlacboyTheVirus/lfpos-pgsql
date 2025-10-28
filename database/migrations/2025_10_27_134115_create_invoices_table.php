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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // IN-001, IN-002, etc.
            $table->foreignId('customer_id')->constrained();
            $table->date('date')->default(now()->toDateString());
            $table->integer('subtotal')->default(0); // Amount in cents
            $table->integer('discount')->default(0); // Amount in cents
            $table->integer('round_off')->default(0); // Amount in cents (+/- rounding)
            $table->integer('total')->default(0); // Amount in cents
            $table->integer('paid')->default(0); // Amount in cents
            $table->integer('due')->default(0); // Amount in cents
            $table->string('status')->default('unpaid');
            $table->boolean('is_paid')->default(false);
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['code']);
            $table->index(['status']);
            $table->index(['is_paid']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
