<?php

namespace App\Console\Commands;

use App\Models\DataPackage;
use App\Services\LegacyDataService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Clear all data packages and re-fetch from the API. Use when you want a clean package list.
 * Also clears reseller_package_prices and storefront_payments that reference packages.
 */
class ClearPackagesAndSyncCommand extends Command
{
    protected $signature = 'packages:clear-and-sync
                            {--force : Skip confirmation}';

    protected $description = 'Clear all data packages and re-sync from LegacyData API. Clears reseller prices and storefront payment records that reference packages.';

    public function handle(): int
    {
        if (! $this->option('force')) {
            if (! $this->confirm('This will delete ALL packages, reseller package prices, and storefront payment records. Then re-fetch from API. Continue?')) {
                $this->info('Aborted.');
                return self::SUCCESS;
            }
        }

        $this->info('Clearing packages and dependent data...');

        // Delete in order so we can drop data_packages (children first due to FK)
        $resellerPrices = DB::table('reseller_package_prices')->count();
        DB::table('reseller_package_prices')->delete();
        $this->line("  Deleted {$resellerPrices} reseller package price(s).");

        $storefrontPayments = 0;
        if (DB::getSchemaBuilder()->hasTable('storefront_payments')) {
            $storefrontPayments = DB::table('storefront_payments')->count();
            DB::table('storefront_payments')->delete();
            $this->line("  Deleted {$storefrontPayments} storefront payment(s).");
        }

        $packageCount = DataPackage::count();
        DataPackage::query()->delete();
        $this->line("  Deleted {$packageCount} package(s).");
        $this->info('Packages table cleared.');

        $this->info('Syncing from LegacyData API...');
        $result = LegacyDataService::syncPackagesToDb();

        if (isset($result['error']) && $result['error']) {
            $this->error('Sync failed: ' . ($result['message'] ?? $result['error']));
            return self::FAILURE;
        }

        $created = $result['created'] ?? 0;
        $updated = $result['updated'] ?? 0;
        $total = $result['total'] ?? 0;
        $this->info("Sync complete. Created: {$created}, Updated: {$updated}, Total: {$total}.");
        return self::SUCCESS;
    }
}
