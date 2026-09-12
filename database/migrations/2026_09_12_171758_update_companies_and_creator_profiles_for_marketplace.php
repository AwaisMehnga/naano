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
        $this->dropForeignKeys('companies', ['user_id']);
        $this->dropForeignKeys('creator_profiles', ['user_id']);

        Schema::table('companies', function (Blueprint $table) {
            $table->dropUnique(['user_id']);
            $table->index('user_id');
            $table->string('name')->nullable();
            $table->string('billing_email')->nullable();
            $table->string('country', 8)->nullable();
            $table->string('stripe_customer_id')->nullable();
            $table->softDeletes();
        });

        Schema::table('creator_profiles', function (Blueprint $table) {
            $table->dropUnique(['user_id']);
            $table->string('display_name')->nullable();
            $table->text('bio')->nullable();
            $table->string('vetting_status')->default('pending');
            $table->string('stripe_connect_id')->nullable();
            $table->softDeletes();
        });

        DB::statement('create unique index creator_profiles_user_id_active_unique on creator_profiles (user_id) where deleted_at is null');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('drop index if exists creator_profiles_user_id_active_unique');

        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['name', 'billing_email', 'country', 'stripe_customer_id', 'deleted_at']);
            $table->dropIndex(['user_id']);
            $table->unique('user_id');
        });

        Schema::table('creator_profiles', function (Blueprint $table) {
            $table->dropColumn(['display_name', 'bio', 'vetting_status', 'stripe_connect_id', 'deleted_at']);
            $table->unique('user_id');
        });
    }

    /**
     * @param  list<string>  $columns
     */
    private function dropForeignKeys(string $tableName, array $columns): void
    {
        Schema::table($tableName, function (Blueprint $table) use ($columns): void {
            $table->dropForeign($columns);
        });
    }
};
