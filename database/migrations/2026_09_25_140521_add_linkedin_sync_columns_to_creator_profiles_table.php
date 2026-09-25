<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('creator_profiles', function (Blueprint $table) {
            $table->jsonb('linkedin_profile')->nullable()->after('linkedin_url');
            $table->jsonb('linkedin_posts')->nullable()->after('linkedin_profile');
            $table->string('linkedin_verify_code', 8)->nullable()->after('linkedin_posts');
            $table->timestamp('linkedin_verified_at')->nullable()->after('linkedin_verify_code');
            $table->timestamp('linkedin_synced_at')->nullable()->after('linkedin_verified_at');
        });
    }

    public function down(): void
    {
        Schema::table('creator_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'linkedin_profile',
                'linkedin_posts',
                'linkedin_verify_code',
                'linkedin_verified_at',
                'linkedin_synced_at',
            ]);
        });
    }
};
