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
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->boolean('email_invites')->default(true);
            $table->boolean('email_applications')->default(true);
            $table->boolean('email_campaign_updates')->default(true);
            $table->boolean('email_messages')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        DB::statement('create unique index notification_preferences_user_id_active_unique on notification_preferences (user_id) where deleted_at is null');

        Schema::create('creator_match_scores', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('campaign_id')->index();
            $table->unsignedBigInteger('creator_profile_id')->index();
            $table->unsignedTinyInteger('fit_score');
            $table->jsonb('reasons')->nullable();
            $table->timestamp('computed_at');
            $table->timestamps();
            $table->softDeletes();
        });

        DB::statement('create unique index creator_match_scores_campaign_creator_active_unique on creator_match_scores (campaign_id, creator_profile_id) where deleted_at is null');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('creator_match_scores');
        Schema::dropIfExists('notification_preferences');
    }
};
