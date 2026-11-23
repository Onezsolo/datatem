<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'referrer_signup_bonus_paid')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('referrer_signup_bonus_paid')->default(false)->after('referred_by_affiliate_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'referrer_signup_bonus_paid')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('referrer_signup_bonus_paid');
            });
        }
    }
};
