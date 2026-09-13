<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tracking_clicks', function (Blueprint $table) {
            $table->index(['post_id', 'occurred_at']);
        });

        Schema::table('leads', function (Blueprint $table) {
            $table->index(['company_id', 'occurred_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tracking_clicks', function (Blueprint $table) {
            $table->dropIndex(['post_id', 'occurred_at']);
        });

        Schema::table('leads', function (Blueprint $table) {
            $table->dropIndex(['company_id', 'occurred_at']);
        });
    }
};
