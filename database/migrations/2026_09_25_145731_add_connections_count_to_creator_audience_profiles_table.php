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
        Schema::table('creator_audience_profiles', function (Blueprint $table) {
            $table->unsignedInteger('connections_count')->nullable()->after('followers_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('creator_audience_profiles', function (Blueprint $table) {
            $table->dropColumn('connections_count');
        });
    }
};
