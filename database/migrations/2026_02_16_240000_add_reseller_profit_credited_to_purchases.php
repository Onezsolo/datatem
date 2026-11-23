<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Reseller profit is credited when order status becomes 'delivered'; this flag prevents double-credit.
     */
    public function up(): void
    {
        if (Schema::hasTable('purchases') && ! Schema::hasColumn('purchases', 'reseller_profit_credited')) {
            Schema::table('purchases', function (Blueprint $table) {
                $table->boolean('reseller_profit_credited')->default(false)->after('reseller_profit');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('purchases') && Schema::hasColumn('purchases', 'reseller_profit_credited')) {
            Schema::table('purchases', function (Blueprint $table) {
                $table->dropColumn('reseller_profit_credited');
            });
        }
    }
};
