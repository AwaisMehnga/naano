<?php

use Database\Seeders\RoleSeeder;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Seed the creator and company roles required by the app.
     */
    public function up(): void
    {
        (new RoleSeeder)->run();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Role::query()
            ->whereIn('name', ['creator', 'company'])
            ->where('guard_name', 'web')
            ->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
