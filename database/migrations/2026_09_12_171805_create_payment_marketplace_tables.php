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
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedInteger('available_cents')->default(0);
            $table->string('currency')->default('EUR');
            $table->timestamps();
            $table->softDeletes();
        });

        DB::statement('create unique index wallets_company_id_active_unique on wallets (company_id) where deleted_at is null');

        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('wallet_id')->index();
            $table->unsignedBigInteger('campaign_id')->nullable()->index();
            $table->unsignedBigInteger('collaboration_id')->nullable()->index();
            $table->string('type');
            $table->string('direction');
            $table->unsignedInteger('amount_cents');
            $table->string('status');
            $table->string('stripe_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('campaign_id')->nullable()->index();
            $table->string('number');
            $table->unsignedInteger('amount_cents');
            $table->string('status');
            $table->string('stripe_invoice_id')->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->string('pdf_path')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('payouts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('creator_profile_id')->index();
            $table->unsignedBigInteger('collaboration_id')->index();
            $table->unsignedInteger('amount_cents');
            $table->string('status');
            $table->string('stripe_transfer_id')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('collaboration_id')->index();
            $table->string('pdf_path')->nullable();
            $table->string('status');
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        DB::statement('create unique index contracts_collaboration_id_active_unique on contracts (collaboration_id) where deleted_at is null');

        Schema::create('company_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->string('plan');
            $table->string('status');
            $table->string('stripe_subscription_id')->nullable();
            $table->timestamp('current_period_end')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_subscriptions');
        Schema::dropIfExists('contracts');
        Schema::dropIfExists('payouts');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('wallet_transactions');
        Schema::dropIfExists('wallets');
    }
};
