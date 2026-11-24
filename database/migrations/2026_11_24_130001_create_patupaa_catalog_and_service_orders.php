<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patupaa_catalog_items', function (Blueprint $table) {
            $table->id();
            $table->string('remote_type', 30);
            $table->string('remote_id', 80);
            $table->string('plan_code', 120)->nullable();
            $table->string('name');
            $table->string('network', 60)->nullable();
            $table->string('data_value', 60)->nullable();
            $table->string('validity', 60)->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->json('meta')->nullable();
            $table->boolean('remote_enabled')->default(true);
            $table->boolean('admin_enabled')->default(true);
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->unique(['remote_type', 'remote_id']);
            $table->index(['remote_type', 'remote_enabled', 'admin_enabled'], 'patupaa_items_availability_idx');
        });

        Schema::create('service_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reference')->unique();
            $table->string('remote_type', 30);
            $table->foreignId('catalog_item_id')->nullable()->constrained('patupaa_catalog_items')->nullOnDelete();
            $table->string('plan_code', 120)->nullable();
            $table->string('beneficiary')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('address')->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('status', 30)->default('pending');
            $table->string('payment_status', 30)->default('unpaid');
            $table->string('remote_reference')->nullable();
            $table->boolean('wallet_debited')->default(false);
            $table->text('error_message')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'remote_type']);
            $table->index(['remote_reference']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_orders');
        Schema::dropIfExists('patupaa_catalog_items');
    }
};
