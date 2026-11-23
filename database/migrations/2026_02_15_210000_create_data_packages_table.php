<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_packages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('external_id')->unique()->comment('JayBart API package id');
            $table->unsignedInteger('network_id')->default(0);
            $table->string('network')->nullable();
            $table->decimal('volume', 12, 2)->default(0);
            $table->string('volume_gb')->nullable();
            $table->decimal('console_price', 12, 2)->default(0);
            $table->decimal('selling_price', 12, 2)->nullable();
            $table->decimal('walking_customer_price', 12, 2)->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_packages');
    }
};
