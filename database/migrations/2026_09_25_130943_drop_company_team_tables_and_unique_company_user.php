<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('company_members')) {
            $owners = DB::table('company_members')
                ->where('role', 'owner')
                ->whereNull('deleted_at')
                ->orderBy('id')
                ->get(['company_id', 'user_id']);

            foreach ($owners as $owner) {
                DB::table('companies')
                    ->where('id', $owner->company_id)
                    ->whereNull('deleted_at')
                    ->update(['user_id' => $owner->user_id]);
            }

            Schema::drop('company_members');
        }

        Schema::dropIfExists('company_invites');

        DB::statement('CREATE UNIQUE INDEX companies_user_id_unique ON companies (user_id) WHERE deleted_at IS NULL');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS companies_user_id_unique');

        Schema::create('company_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id');
            $table->foreignId('user_id');
            $table->string('role');
            $table->timestamp('invited_at')->nullable();
            $table->timestamp('joined_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('company_invites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id');
            $table->string('email');
            $table->string('role');
            $table->foreignId('invited_by_user_id');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }
};
