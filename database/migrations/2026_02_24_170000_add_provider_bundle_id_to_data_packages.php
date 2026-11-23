<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('data_packages', function (Blueprint $table) {
            if (! Schema::hasColumn('data_packages', 'provider_bundle_id')) {
                $table->string('provider_bundle_id')->nullable()->after('volume_gb');
            }
        });
    }

    public function down(): void
    {
        Schema::table('data_packages', function (Blueprint $table) {
            $table->dropColumn('provider_bundle_id');
        });
    }
};
