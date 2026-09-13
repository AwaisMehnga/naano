<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('tracking_links')->whereNull('slug')->orWhere('slug', '')->orderBy('id')->each(function (object $row): void {
            DB::table('tracking_links')->where('id', $row->id)->update([
                'slug' => Str::lower(Str::random(10)).$row->id,
            ]);
        });

        Schema::table('tracking_links', function (Blueprint $table) {
            $table->unique('slug');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tracking_links', function (Blueprint $table) {
            $table->dropUnique(['slug']);
        });
    }
};
