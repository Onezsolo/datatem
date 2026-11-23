<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('reseller_slug', 64)->nullable()->unique()->after('role');
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->foreignId('reseller_id')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
            $table->decimal('reseller_profit', 10, 2)->nullable()->after('amount');
        });

        // Allow guest (storefront) purchases: user_id nullable
        DB::statement('ALTER TABLE purchases MODIFY user_id BIGINT UNSIGNED NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE purchases MODIFY user_id BIGINT UNSIGNED NOT NULL');
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropForeign(['reseller_id']);
            $table->dropColumn(['reseller_id', 'reseller_profit']);
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('reseller_slug');
        });
    }
};
