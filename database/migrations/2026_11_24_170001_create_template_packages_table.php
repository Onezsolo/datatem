<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('template_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('version', 40)->default('1.0.0');
            $table->text('description')->nullable();
            $table->longText('payload');
            $table->unsignedInteger('applied_count')->default(0);
            $table->timestamp('last_applied_at')->nullable();
            $table->string('last_applied_domain')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('template_packages');
    }
};
