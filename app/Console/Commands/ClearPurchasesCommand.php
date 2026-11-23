<?php

namespace App\Console\Commands;

use App\Models\Purchase;
use Illuminate\Console\Command;

/**
 * Clear all purchases (data orders) for testing. Use with caution.
 */
class ClearPurchasesCommand extends Command
{
    protected $signature = 'purchases:clear
                            {--force : Skip confirmation}';

    protected $description = 'Delete all purchases (orders) from the database for testing. Use with caution.';

    public function handle(): int
    {
        if (! $this->option('force')) {
            if (! $this->confirm('This will delete ALL purchases. Are you sure?')) {
                $this->info('Aborted.');
                return self::SUCCESS;
            }
        }

        $count = Purchase::count();
        Purchase::query()->delete();
        $this->info("Cleared {$count} purchase(s).");
        return self::SUCCESS;
    }
}
