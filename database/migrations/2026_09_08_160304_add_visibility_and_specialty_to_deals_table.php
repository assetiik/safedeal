<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deals', function (Blueprint $table) {
            $table->string('visibility', 16)->default('private')->after('status');
            $table->string('specialty')->nullable()->after('visibility');
            $table->index(['visibility', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('deals', function (Blueprint $table) {
            $table->dropIndex(['visibility', 'status']);
            $table->dropColumn(['visibility', 'specialty']);
        });
    }
};
