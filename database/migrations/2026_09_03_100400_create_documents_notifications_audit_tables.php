<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('deal_id')->constrained()->cascadeOnDelete();
            $table->string('type', 32);
            $table->string('title');
            $table->string('file_name');
            $table->string('mime_type');
            $table->unsignedBigInteger('size_bytes');
            $table->string('storage_key');
            $table->foreignUuid('uploaded_by_user_id')->nullable()->constrained('users');
            $table->timestamps();

            $table->index(['deal_id', 'type']);
        });

        Schema::create('app_notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->string('category', 32);
            $table->string('type', 64);
            $table->string('title');
            $table->text('body');
            $table->json('payload')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'read_at']);
            $table->index(['user_id', 'category']);
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('actor_user_id')->nullable()->constrained('users');
            $table->string('actor_role', 32)->nullable();
            $table->string('action', 64);
            $table->string('entity_type', 64);
            $table->uuid('entity_id')->nullable();
            $table->foreignUuid('deal_id')->nullable()->constrained()->nullOnDelete();
            $table->json('payload')->nullable();
            $table->string('ip', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at');

            $table->index(['entity_type', 'entity_id']);
            $table->index(['deal_id', 'created_at']);
            $table->index(['actor_user_id', 'created_at']);
            $table->index('action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('app_notifications');
        Schema::dropIfExists('documents');
    }
};
