<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('storefront_payments', function (Blueprint $table) {
            $table->decimal('amount_charged', 10, 2)->nullable()->after('amount');
        });
    }

    public function down(): void
    {
        Schema::table('storefront_payments', function (Blueprint $table) {
            $table->dropColumn('amount_charged');
        });
    }
};
