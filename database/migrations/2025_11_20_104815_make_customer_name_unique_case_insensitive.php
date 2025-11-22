<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop the existing unique constraint on name
        DB::statement('ALTER TABLE customers DROP CONSTRAINT IF EXISTS customers_name_unique');

        // Create a case-insensitive unique index on LOWER(name)
        DB::statement('CREATE UNIQUE INDEX customers_name_unique_case_insensitive ON customers (LOWER(name))');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the case-insensitive unique index
        DB::statement('DROP INDEX IF EXISTS customers_name_unique_case_insensitive');

        // Recreate the original case-sensitive unique constraint
        DB::statement('ALTER TABLE customers ADD CONSTRAINT customers_name_unique UNIQUE (name)');
    }
};
