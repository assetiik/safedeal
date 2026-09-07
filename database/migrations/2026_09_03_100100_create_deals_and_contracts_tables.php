<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('deal_number')->unique();
            $table->string('status', 32);
            $table->string('title');
            $table->text('description');
            $table->unsignedBigInteger('amount_tenge');
            $table->char('currency', 3)->default('KZT');
            $table->unsignedInteger('commission_rate_bps')->default(0);
            $table->unsignedBigInteger('commission_amount_tenge')->default(0);
            $table->date('deadline');
            $table->text('terms');
            $table->text('additional_terms')->nullable();
            $table->json('required_documents')->nullable();
            $table->foreignUuid('customer_user_id')->constrained('users');
            $table->foreignUuid('contractor_user_id')->nullable()->constrained('users');
            $table->string('contractor_invite_email');
            $table->boolean('customer_confirmed_contract')->default(false);
            $table->boolean('contractor_confirmed_contract')->default(false);
            $table->boolean('funds_frozen')->default(false);
            $table->timestamps();

            $table->index(['customer_user_id', 'status']);
            $table->index(['contractor_user_id', 'status']);
            $table->index('contractor_invite_email');
            $table->index('updated_at');
        });

        Schema::create('contracts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('deal_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('template_version')->default('v1');
            $table->longText('body_snapshot')->nullable();
            $table->string('signature_type', 16)->default('simple');
            $table->timestamp('customer_confirmed_at')->nullable();
            $table->timestamp('contractor_confirmed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contracts');
        Schema::dropIfExists('deals');
    }
};
