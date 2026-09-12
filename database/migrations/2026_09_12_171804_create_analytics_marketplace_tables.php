<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('post_metrics', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('post_id')->index();
            $table->unsignedBigInteger('impressions')->default(0);
            $table->unsignedInteger('likes')->default(0);
            $table->unsignedInteger('comments')->default(0);
            $table->unsignedInteger('clicks')->default(0);
            $table->unsignedInteger('qualified_clicks')->default(0);
            $table->unsignedInteger('leads_count')->default(0);
            $table->unsignedInteger('cpm_cents')->nullable();
            $table->timestamp('captured_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        DB::statement('create unique index post_metrics_post_id_active_unique on post_metrics (post_id) where deleted_at is null');

        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('campaign_id')->index();
            $table->unsignedBigInteger('post_id')->nullable()->index();
            $table->unsignedBigInteger('tracking_link_id')->nullable()->index();
            $table->timestamp('occurred_at');
            $table->string('source');
            $table->jsonb('payload')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leads');
        Schema::dropIfExists('post_metrics');
    }
};
