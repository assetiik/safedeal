<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('deal_id')->constrained()->cascadeOnDelete();
            $table->string('type', 32);
            $table->unsignedBigInteger('amount_tenge');
            $table->char('currency', 3)->default('KZT');
            $table->string('status', 32);
            $table->string('provider', 32);
            $table->string('provider_payment_id')->nullable();
            $table->string('direction', 16);
            $table->string('idempotency_key')->unique();
            $table->json('provider_payload')->nullable();
            $table->timestamps();

            $table->index(['deal_id', 'type', 'status']);
            $table->index('provider_payment_id');
        });

        Schema::create('idempotency_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->string('key');
            $table->string('request_hash', 64);
            $table->unsignedSmallInteger('response_code');
            $table->json('response_body');
            $table->timestamps();

            $table->unique(['user_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('idempotency_keys');
        Schema::dropIfExists('payments');
    }
};
