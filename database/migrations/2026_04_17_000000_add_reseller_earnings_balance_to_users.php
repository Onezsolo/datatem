<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'reseller_earnings_balance')) {
            Schema::table('users', function (Blueprint $table) {
                $table->decimal('reseller_earnings_balance', 12, 2)->default(0)->after('balance');
            });
        }

        if (! Schema::hasTable('purchases') || ! Schema::hasColumn('users', 'reseller_earnings_balance')) {
            return;
        }

        $totals = DB::table('purchases')
            ->selectRaw('reseller_id, COALESCE(SUM(reseller_profit), 0) as profit_sum')
            ->where('reseller_profit_credited', true)
            ->whereNotNull('reseller_id')
            ->groupBy('reseller_id')
            ->get();

        foreach ($totals as $row) {
            $balance = (float) (DB::table('users')->where('id', $row->reseller_id)->value('balance') ?? 0);
            $capped = round(min($balance, (float) $row->profit_sum), 2);
            if ($capped > 0) {
                DB::table('users')->where('id', $row->reseller_id)->update(['reseller_earnings_balance' => $capped]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'reseller_earnings_balance')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('reseller_earnings_balance');
            });
        }
    }
};
