<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('delivery_name')->nullable()->after('phone');
            $table->string('delivery_phone', 20)->nullable()->after('delivery_name');
            $table->string('delivery_address', 500)->nullable()->after('delivery_phone');
            $table->string('delivery_city', 120)->nullable()->after('delivery_address');
            $table->string('delivery_region', 120)->nullable()->after('delivery_city');
            $table->string('delivery_zone', 40)->nullable()->after('delivery_region');
            $table->string('delivery_notes', 1000)->nullable()->after('delivery_zone');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'delivery_name',
                'delivery_phone',
                'delivery_address',
                'delivery_city',
                'delivery_region',
                'delivery_zone',
                'delivery_notes',
            ]);
        });
    }
};
