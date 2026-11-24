<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shop_orders', function (Blueprint $table) {
            $table->string('delivery_zone', 40)->nullable();
        });

        Schema::table('shop_payments', function (Blueprint $table) {
            $table->timestamp('refunded_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('shop_payments', function (Blueprint $table) {
            $table->dropColumn('refunded_at');
        });

        Schema::table('shop_orders', function (Blueprint $table) {
            $table->dropColumn('delivery_zone');
        });
    }
};
