<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Remove demo / marketplace rows while keeping roles and niches.
     *
     * @var list<string>
     */
    private array $tables = [
        'agent_conversation_messages',
        'agent_conversations',
        'tracking_events',
        'tracking_clicks',
        'tracking_links',
        'leads',
        'post_metrics',
        'post_reviews',
        'media',
        'posts',
        'collaboration_events',
        'collaborations',
        'contracts',
        'payouts',
        'invoices',
        'wallet_transactions',
        'wallets',
        'stripe_events',
        'messages',
        'conversations',
        'campaigns',
        'company_icps',
        'company_subscriptions',
        'company_invites',
        'creator_match_scores',
        'creator_niche',
        'creator_offers',
        'creator_audience_profiles',
        'creator_profiles',
        'companies',
        'notification_preferences',
        'notifications',
        'model_has_roles',
        'model_has_permissions',
        'passkeys',
        'password_reset_tokens',
        'sessions',
        'users',
        'telescope_entries_tags',
        'telescope_entries',
        'telescope_monitoring',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        $tables = array_values(array_filter(
            $this->tables,
            fn (string $table): bool => Schema::hasTable($table),
        ));

        if ($tables === []) {
            return;
        }

        $quoted = implode(', ', array_map(
            fn (string $table): string => '"'.str_replace('"', '""', $table).'"',
            $tables,
        ));

        DB::statement("TRUNCATE TABLE {$quoted} RESTART IDENTITY CASCADE");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Demo rows cannot be restored.
    }
};
