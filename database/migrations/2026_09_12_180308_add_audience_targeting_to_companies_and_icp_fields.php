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
        Schema::table('companies', function (Blueprint $table) {
            $table->jsonb('audience_targeting')->nullable();
        });

        Schema::table('company_icps', function (Blueprint $table) {
            $table->jsonb('tags')->nullable();
            $table->jsonb('industries')->nullable();
            $table->jsonb('regions')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('audience_targeting');
        });

        Schema::table('company_icps', function (Blueprint $table) {
            $table->dropColumn(['tags', 'industries', 'regions']);
        });
    }
};
