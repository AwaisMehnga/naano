<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('creator_profiles', function (Blueprint $table) {
            $table->boolean('payouts_enabled')->default(false);
        });

        Schema::table('payouts', function (Blueprint $table) {
            $table->unsignedBigInteger('collaboration_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('payouts', function (Blueprint $table) {
            $table->unsignedBigInteger('collaboration_id')->nullable(false)->change();
        });

        Schema::table('creator_profiles', function (Blueprint $table) {
            $table->dropColumn('payouts_enabled');
        });
    }
};
