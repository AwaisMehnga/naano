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
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('collaboration_id')->index();
            $table->string('status')->default('draft');
            $table->text('body')->nullable();
            $table->text('review_note')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->string('published_url')->nullable();
            $table->string('linkedin_post_id')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->unsignedBigInteger('reviewed_by_user_id')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('tracking_links', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('post_id')->nullable()->index();
            $table->unsignedBigInteger('collaboration_id')->index();
            $table->string('destination_url');
            $table->string('utm_source')->nullable();
            $table->string('utm_medium')->nullable();
            $table->string('utm_campaign')->nullable();
            $table->string('utm_content')->nullable();
            $table->string('slug')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tracking_links');
        Schema::dropIfExists('posts');
    }
};
