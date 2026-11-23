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
        if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'referred_by_affiliate_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('referred_by_affiliate_id')->nullable()->constrained('affiliates')->nullOnDelete();
            });
        }

        if (Schema::hasTable('purchases') && ! Schema::hasColumn('purchases', 'affiliate_commission_credited')) {
            Schema::table('purchases', function (Blueprint $table) {
                $table->boolean('affiliate_commission_credited')->default(false)->after('status');
            });
        }

        if (Schema::hasTable('affiliate_withdrawals') && ! Schema::hasColumn('affiliate_withdrawals', 'fee_amount')) {
            Schema::table('affiliate_withdrawals', function (Blueprint $table) {
                $table->decimal('fee_amount', 12, 2)->nullable()->after('amount');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('users', 'referred_by_affiliate_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign(['referred_by_affiliate_id']);
            });
        }
        if (Schema::hasTable('purchases') && Schema::hasColumn('purchases', 'affiliate_commission_credited')) {
            Schema::table('purchases', function (Blueprint $table) {
                $table->dropColumn('affiliate_commission_credited');
            });
        }
        if (Schema::hasTable('affiliate_withdrawals') && Schema::hasColumn('affiliate_withdrawals', 'fee_amount')) {
            Schema::table('affiliate_withdrawals', function (Blueprint $table) {
                $table->dropColumn('fee_amount');
            });
        }
    }
};
