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
        DB::statement('ALTER TABLE products DROP CONSTRAINT IF EXISTS products_name_unique');

        // Create a case-insensitive unique index on LOWER(name)
        DB::statement('CREATE UNIQUE INDEX products_name_unique_case_insensitive ON products (LOWER(name))');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the case-insensitive unique index
        DB::statement('DROP INDEX IF EXISTS products_name_unique_case_insensitive');

        // Recreate the original case-sensitive unique constraint
        DB::statement('ALTER TABLE products ADD CONSTRAINT products_name_unique UNIQUE (name)');
    }
};
