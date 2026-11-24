<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('template_licenses', function (Blueprint $table) {
            $table->id();
            $table->string('license_key_hash', 64)->unique();
            $table->string('key_prefix', 24);
            $table->string('client_name');
            $table->string('client_email')->nullable();
            $table->string('domain')->nullable();
            $table->string('agreement_reference')->nullable();
            $table->timestamp('issued_at');
            $table->timestamp('expires_at')->nullable();
            $table->string('status', 20)->default('active');
            $table->text('notes')->nullable();
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('template_licenses');
    }
};
