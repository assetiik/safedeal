<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disputes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('deal_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('status', 32);
            $table->text('reason');
            $table->foreignUuid('opened_by_user_id')->constrained('users');
            $table->string('resolution_type', 32)->nullable();
            $table->text('resolution_note')->nullable();
            $table->unsignedBigInteger('customer_amount_tenge')->nullable();
            $table->unsignedBigInteger('contractor_amount_tenge')->nullable();
            $table->foreignUuid('resolved_by_admin_id')->nullable()->constrained('users');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index('status');
        });

        Schema::create('dispute_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('dispute_id')->constrained()->cascadeOnDelete();
            $table->string('type', 32);
            $table->foreignUuid('actor_user_id')->nullable()->constrained('users');
            $table->json('payload')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dispute_events');
        Schema::dropIfExists('disputes');
    }
};
