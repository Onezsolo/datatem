<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_providers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('driver', 40)->default('patupaa');
            $table->string('base_url');
            $table->text('api_key')->nullable();
            $table->text('api_secret')->nullable();
            $table->json('services')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamp('last_synced_at')->nullable();
            $table->text('last_sync_error')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'is_default']);
        });

        Schema::table('patupaa_catalog_items', function (Blueprint $table) {
            $table->dropUnique(['remote_type', 'remote_id']);
            $table->foreignId('provider_id')->nullable()->after('id')->constrained('service_providers')->nullOnDelete();
            $table->unique(['provider_id', 'remote_type', 'remote_id']);
        });

        Schema::table('service_orders', function (Blueprint $table) {
            $table->foreignId('provider_id')->nullable()->after('catalog_item_id')->constrained('service_providers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('service_orders', function (Blueprint $table) {
            $table->dropForeign(['provider_id']);
            $table->dropColumn('provider_id');
        });

        Schema::table('patupaa_catalog_items', function (Blueprint $table) {
            $table->dropUnique(['provider_id', 'remote_type', 'remote_id']);
            $table->dropForeign(['provider_id']);
            $table->dropColumn('provider_id');
            $table->unique(['remote_type', 'remote_id']);
        });

        Schema::dropIfExists('service_providers');
    }
};
