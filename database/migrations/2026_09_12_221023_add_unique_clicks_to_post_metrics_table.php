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
        Schema::table('post_metrics', function (Blueprint $table) {
            $table->unsignedInteger('unique_clicks')->default(0)->after('clicks');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('post_metrics', function (Blueprint $table) {
            $table->dropColumn('unique_clicks');
        });
    }
};
