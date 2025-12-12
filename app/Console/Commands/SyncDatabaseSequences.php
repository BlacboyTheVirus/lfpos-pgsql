<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SyncDatabaseSequences extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:sync-sequences
                          {--table= : Specific table to sync (optional)}
                          {--dry-run : Show what would be changed without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize PostgreSQL sequences with actual max IDs in tables';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Check if we're using PostgreSQL
        if (DB::connection()->getDriverName() !== 'pgsql') {
            $this->error('This command only works with PostgreSQL databases.');
            return self::FAILURE;
        }

        $isDryRun = $this->option('dry-run');
        $specificTable = $this->option('table');

        if ($isDryRun) {
            $this->info('Running in DRY-RUN mode - no changes will be made');
            $this->newLine();
        }

        // Get list of tables to check
        $tables = $specificTable
            ? [$specificTable]
            : $this->getAllTablesWithSequences();

        if (empty($tables)) {
            $this->warn('No tables found to sync.');
            return self::SUCCESS;
        }

        $this->info('Checking ' . count($tables) . ' table(s) for sequence synchronization...');
        $this->newLine();

        $syncedCount = 0;
        $skippedCount = 0;
        $errorCount = 0;

        foreach ($tables as $table) {
            try {
                $result = $this->syncTableSequence($table, $isDryRun);

                if ($result === 'synced') {
                    $syncedCount++;
                } elseif ($result === 'skipped') {
                    $skippedCount++;
                }
            } catch (\Exception $e) {
                $this->error("✗ Error syncing {$table}: " . $e->getMessage());
                $errorCount++;
            }
        }

        // Summary
        $this->newLine();
        $this->info('Summary:');
        $this->line("  Synced: {$syncedCount}");
        $this->line("  Already correct: {$skippedCount}");
        if ($errorCount > 0) {
            $this->line("  Errors: {$errorCount}");
        }

        if ($isDryRun && $syncedCount > 0) {
            $this->newLine();
            $this->comment('Run without --dry-run to apply these changes.');
        }

        return $errorCount > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Get all tables that have auto-incrementing sequences.
     */
    protected function getAllTablesWithSequences(): array
    {
        // Common tables in the application that use auto-increment
        $potentialTables = [
            'users',
            'customers',
            'products',
            'invoices',
            'invoice_products',
            'invoice_payments',
            'expenses',
            'settings',
            'notifications',
            'sessions',
            'jobs',
            'failed_jobs',
        ];

        // Filter to only existing tables
        return array_filter($potentialTables, function ($table) {
            return Schema::hasTable($table) && Schema::hasColumn($table, 'id');
        });
    }

    /**
     * Sync sequence for a specific table.
     *
     * @return string 'synced', 'skipped', or 'error'
     */
    protected function syncTableSequence(string $table, bool $isDryRun): string
    {
        $sequenceName = "{$table}_id_seq";

        // Check if sequence exists
        $sequenceExists = DB::selectOne(
            "SELECT EXISTS (
                SELECT 1 FROM pg_class
                WHERE relkind = 'S' AND relname = ?
            ) as exists",
            [$sequenceName]
        );

        if (!$sequenceExists->exists) {
            $this->comment("  {$table}: No sequence found (skipping)");
            return 'skipped';
        }

        // Get current sequence value
        $currentSeq = DB::selectOne("SELECT last_value FROM {$sequenceName}")->last_value;

        // Get max ID from table
        $maxId = DB::table($table)->max('id') ?? 0;

        // Check if sync is needed
        if ($maxId <= $currentSeq) {
            $this->line("  {$table}: ✓ Already correct (seq: {$currentSeq}, max: {$maxId})");
            return 'skipped';
        }

        // Sequence needs to be updated
        $difference = $maxId - $currentSeq;

        if ($isDryRun) {
            $this->warn("  {$table}: Would sync sequence {$currentSeq} → {$maxId} (diff: +{$difference})");
        } else {
            // Actually update the sequence
            DB::statement("SELECT setval('{$sequenceName}', ?, true)", [$maxId]);
            $this->info("  {$table}: ✓ Synced sequence {$currentSeq} → {$maxId} (diff: +{$difference})");
        }

        return 'synced';
    }
}
