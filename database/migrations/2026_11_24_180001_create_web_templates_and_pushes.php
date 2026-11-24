<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('web_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('source_path')->nullable();
            $table->string('source_git_url')->nullable();
            $table->string('source_branch', 120)->default('main');
            $table->string('version', 40)->default('1.0.0');
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('last_pushed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('web_template_pushes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('web_template_id')->constrained('web_templates')->cascadeOnDelete();
            $table->foreignId('pushed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('owner');
            $table->string('repository');
            $table->string('branch', 120)->default('main');
            $table->string('repo_url')->nullable();
            $table->string('status', 20)->default('pending');
            $table->text('message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('web_template_pushes');
        Schema::dropIfExists('web_templates');
    }
};
