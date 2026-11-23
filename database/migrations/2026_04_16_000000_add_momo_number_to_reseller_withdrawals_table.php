<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('reseller_withdrawals') && ! Schema::hasColumn('reseller_withdrawals', 'momo_number')) {
            Schema::table('reseller_withdrawals', function (Blueprint $table) {
                $table->string('momo_number', 32)->nullable()->after('amount');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('reseller_withdrawals') && Schema::hasColumn('reseller_withdrawals', 'momo_number')) {
            Schema::table('reseller_withdrawals', function (Blueprint $table) {
                $table->dropColumn('momo_number');
            });
        }
    }
};
