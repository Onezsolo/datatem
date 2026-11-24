<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('template_licenses', function (Blueprint $table) {
            $table->string('product_key', 80)->nullable()->after('key_prefix');
            $table->string('license_tier', 40)->nullable()->after('product_key');
            $table->decimal('price', 12, 2)->nullable()->after('license_tier');
            $table->unsignedInteger('deployments_allowed')->nullable()->after('price');
            $table->timestamp('support_ends_at')->nullable()->after('expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('template_licenses', function (Blueprint $table) {
            $table->dropColumn(['product_key', 'license_tier', 'price', 'deployments_allowed', 'support_ends_at']);
        });
    }
};
