<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('storefront_payments', function (Blueprint $table) {
            $table->string('id', 64)->primary(); // Paystack reference
            $table->foreignId('reseller_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('data_package_id')->constrained('data_packages')->cascadeOnDelete();
            $table->string('beneficiary', 20);
            $table->decimal('amount', 10, 2);
            $table->decimal('reseller_profit', 10, 2);
            $table->decimal('base_amount', 10, 2); // package selling_price (platform share)
            $table->string('status', 20)->default('pending');
            $table->string('customer_email')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('storefront_payments');
    }
};
