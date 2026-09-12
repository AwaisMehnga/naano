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
        Schema::create('company_members', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('role');
            $table->timestamp('invited_at')->nullable();
            $table->timestamp('joined_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        DB::statement('create unique index company_members_company_user_active_unique on company_members (company_id, user_id) where deleted_at is null');

        Schema::create('company_icps', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->string('title');
            $table->text('description');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('niches', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        DB::statement('create unique index niches_name_active_unique on niches (name) where deleted_at is null');
        DB::statement('create unique index niches_slug_active_unique on niches (slug) where deleted_at is null');

        Schema::create('creator_niche', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('creator_profile_id')->index();
            $table->unsignedBigInteger('niche_id')->index();
            $table->timestamps();
            $table->softDeletes();
        });

        DB::statement('create unique index creator_niche_profile_niche_active_unique on creator_niche (creator_profile_id, niche_id) where deleted_at is null');

        Schema::create('creator_audience_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('creator_profile_id')->index();
            $table->string('network')->default('linkedin');
            $table->unsignedInteger('followers_count')->nullable();
            $table->jsonb('audience_mix')->nullable();
            $table->timestamp('captured_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('creator_offers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('creator_profile_id')->index();
            $table->string('network')->default('linkedin');
            $table->string('label');
            $table->unsignedInteger('posts_count')->default(1);
            $table->unsignedInteger('price_cents');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        $now = now();

        foreach (DB::table('companies')->whereNotNull('user_id')->get() as $company) {
            $exists = DB::table('company_members')
                ->where('company_id', $company->id)
                ->where('user_id', $company->user_id)
                ->whereNull('deleted_at')
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('company_members')->insert([
                'company_id' => $company->id,
                'user_id' => $company->user_id,
                'role' => 'owner',
                'joined_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('creator_offers');
        Schema::dropIfExists('creator_audience_profiles');
        Schema::dropIfExists('creator_niche');
        Schema::dropIfExists('niches');
        Schema::dropIfExists('company_icps');
        Schema::dropIfExists('company_members');
    }
};
