<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds performance optimization indexes based on PERFORMANCE_OPTIMIZATION.md
     * document analysis conducted on 2025-11-25.
     *
     * These indexes improve query performance for:
     * - Date range filtering on invoices
     * - Payment type filtering on invoice payments
     * - Date and category filtering on expenses
     * - Creator-based filtering across tables
     */
    public function up(): void
    {
        // INDEX #1: HIGH PRIORITY - Composite index for date range + customer filtering on invoices
        Schema::table('invoices', function (Blueprint $table) {
            $table->index(['date', 'customer_id'], 'invoices_date_customer_idx');
        });

        // INDEX #2: MEDIUM PRIORITY - Index for payment type filtering on invoice_payments
        Schema::table('invoice_payments', function (Blueprint $table) {
            $table->index('payment_type', 'invoice_payments_payment_type_idx');
        });

        // INDEX #3: MEDIUM PRIORITY - Composite index for date + category filtering on expenses
        Schema::table('expenses', function (Blueprint $table) {
            $table->index(['date', 'category'], 'expenses_date_category_idx');
        });

        // INDEX #3 (continued): Single index for category filtering on expenses
        Schema::table('expenses', function (Blueprint $table) {
            $table->index('category', 'expenses_category_idx');
        });

        // INDEX #4: LOW PRIORITY - Foreign key indexes for faster creator filtering
        Schema::table('customers', function (Blueprint $table) {
            $table->index('created_by', 'customers_created_by_idx');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->index('created_by', 'products_created_by_idx');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->index('created_by', 'expenses_created_by_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove all added indexes in reverse order
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropIndex('expenses_created_by_idx');
            $table->dropIndex('expenses_category_idx');
            $table->dropIndex('expenses_date_category_idx');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_created_by_idx');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex('customers_created_by_idx');
        });

        Schema::table('invoice_payments', function (Blueprint $table) {
            $table->dropIndex('invoice_payments_payment_type_idx');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex('invoices_date_customer_idx');
        });
    }
};
