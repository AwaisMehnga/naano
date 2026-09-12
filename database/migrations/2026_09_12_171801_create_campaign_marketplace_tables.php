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
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('company_icp_id')->nullable()->index();
            $table->string('name');
            $table->string('type');
            $table->string('objective');
            $table->string('status')->default('draft');
            $table->unsignedInteger('budget_cents')->nullable();
            $table->text('goal')->nullable();
            $table->jsonb('key_messages')->nullable();
            $table->text('guidelines')->nullable();
            $table->timestamp('start_at')->nullable();
            $table->timestamp('end_at')->nullable();
            $table->unsignedBigInteger('created_by_user_id')->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('collaborations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('campaign_id')->index();
            $table->unsignedBigInteger('creator_profile_id')->index();
            $table->unsignedBigInteger('creator_offer_id')->nullable()->index();
            $table->string('source');
            $table->string('status');
            $table->unsignedInteger('booked_price_cents')->nullable();
            $table->unsignedInteger('booked_posts_count')->nullable();
            $table->unsignedBigInteger('invited_by_user_id')->nullable()->index();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('booked_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        DB::statement('create unique index collaborations_campaign_creator_active_unique on collaborations (campaign_id, creator_profile_id) where deleted_at is null');

        Schema::create('collaboration_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('collaboration_id')->index();
            $table->unsignedBigInteger('actor_user_id')->nullable()->index();
            $table->string('type');
            $table->text('body')->nullable();
            $table->jsonb('meta')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('collaboration_events');
        Schema::dropIfExists('collaborations');
        Schema::dropIfExists('campaigns');
    }
};
