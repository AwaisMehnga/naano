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
        Schema::create('tracking_clicks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tracking_link_id')->index();
            $table->unsignedBigInteger('post_id')->nullable()->index();
            $table->uuid('visitor_key')->index();
            $table->string('ip_hash', 64)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();
        });

        Schema::create('tracking_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tracking_link_id')->index();
            $table->unsignedBigInteger('post_id')->nullable()->index();
            $table->uuid('visitor_key')->index();
            $table->string('type');
            $table->jsonb('payload')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tracking_events');
        Schema::dropIfExists('tracking_clicks');
    }
};
