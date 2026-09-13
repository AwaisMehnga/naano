<?php

use App\Http\Controllers\Api\Company\AnalyticsController as CompanyAnalyticsController;
use App\Http\Controllers\Api\Company\AudienceController as CompanyAudienceController;
use App\Http\Controllers\Api\Company\CampaignAnalyticsController;
use App\Http\Controllers\Api\Company\CampaignCollaborationController;
use App\Http\Controllers\Api\Company\CampaignController;
use App\Http\Controllers\Api\Company\CampaignLeadController;
use App\Http\Controllers\Api\Company\CampaignReportController;
use App\Http\Controllers\Api\Company\CampaignTrackingLinkController;
use App\Http\Controllers\Api\Company\CollaborationActionController;
use App\Http\Controllers\Api\Company\CollaborationController as CompanyCollaborationController;
use App\Http\Controllers\Api\Company\CollaborationMessageController as CompanyCollaborationMessageController;
use App\Http\Controllers\Api\Company\CompanyProfileController;
use App\Http\Controllers\Api\Company\CreatorController;
use App\Http\Controllers\Api\Company\IcpController;
use App\Http\Controllers\Api\Company\LeadController as CompanyLeadController;
use App\Http\Controllers\Api\Company\MemberController;
use App\Http\Controllers\Api\Company\PostController as CompanyPostController;
use App\Http\Controllers\Api\Company\PostMetricController as CompanyPostMetricController;
use App\Http\Controllers\Api\Company\TrackingLinkController;
use App\Http\Controllers\Api\Company\WalletController;
use App\Http\Controllers\Api\Company\WorkspaceController;
use App\Http\Controllers\Api\Creator\AnalyticsController as CreatorAnalyticsController;
use App\Http\Controllers\Api\Creator\AudienceController as CreatorAudienceController;
use App\Http\Controllers\Api\Creator\BillingController;
use App\Http\Controllers\Api\Creator\CollaborationController as CreatorCollaborationController;
use App\Http\Controllers\Api\Creator\CollaborationMessageController as CreatorCollaborationMessageController;
use App\Http\Controllers\Api\Creator\CollaborationMetricController;
use App\Http\Controllers\Api\Creator\ConnectController;
use App\Http\Controllers\Api\Creator\CreatorAccountController;
use App\Http\Controllers\Api\Creator\CreatorWalletController;
use App\Http\Controllers\Api\Creator\NicheController as CreatorNicheController;
use App\Http\Controllers\Api\Creator\OpportunityController;
use App\Http\Controllers\Api\Creator\PayoutController;
use App\Http\Controllers\Api\Creator\PostController as CreatorPostController;
use App\Http\Controllers\Api\Creator\PostMetricController as CreatorPostMetricController;
use App\Http\Controllers\Api\Creator\ProfileController as CreatorProfileController;
use App\Http\Controllers\Api\NichesController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\NotificationPreferenceController;
use App\Http\Controllers\Api\StripeWebhookController;
use App\Http\Controllers\Api\TrackingEventController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::post('stripe/webhook', [StripeWebhookController::class, 'store'])->name('stripe.webhook');
Route::post('t/{slug}/events', [TrackingEventController::class, 'store'])
    ->where('slug', '[A-Za-z0-9]+')
    ->name('tracking.events');

Route::middleware('auth')->group(function () {
    Route::apiSingleton('user', UserController::class)->only(['show'])->names(['show' => 'user']);
    Route::get('niches', [NichesController::class, 'index'])->name('niches.index');

    Route::middleware('verified')->group(function () {
        Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::post('notifications/read', [NotificationController::class, 'readAll'])->name('notifications.read-all');
        Route::post('notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');
        Route::get('notification-preferences', [NotificationPreferenceController::class, 'show'])->name('notification-preferences.show');
        Route::put('notification-preferences', [NotificationPreferenceController::class, 'update'])->name('notification-preferences.update');
    });

    Route::middleware(['verified', 'role:company', 'onboarded', 'current.company'])->prefix('company')->name('company.')->group(function () {
        Route::get('ping', [UserController::class, 'show'])->name('ping');
        Route::apiResource('workspaces', WorkspaceController::class)->only(['index', 'update']);
        Route::get('profile', [CompanyProfileController::class, 'show'])->name('profile.show');
        Route::match(['put', 'patch'], 'profile', [CompanyProfileController::class, 'update'])->name('profile.update');
        Route::apiSingleton('audience', CompanyAudienceController::class)->only(['show', 'update']);
        Route::apiResource('icps', IcpController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::apiResource('members', MemberController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::get('wallet', [WalletController::class, 'show'])->name('wallet.show');
        Route::get('wallet/transactions', [WalletController::class, 'transactions'])->name('wallet.transactions');
        Route::post('wallet/topups', [WalletController::class, 'storeTopup'])->name('wallet.topups.store');
        Route::get('wallet/topups/{walletTransaction}', [WalletController::class, 'showTopup'])->name('wallet.topups.show');
        Route::apiResource('campaigns', CampaignController::class)->only(['index', 'store', 'show', 'update']);
        Route::post('campaigns/{campaign}/launch', [CampaignController::class, 'launch'])->name('campaigns.launch');
        Route::post('campaigns/{campaign}/pause', [CampaignController::class, 'pause'])->name('campaigns.pause');
        Route::post('campaigns/{campaign}/resume', [CampaignController::class, 'resume'])->name('campaigns.resume');
        Route::post('campaigns/{campaign}/reopen', [CampaignController::class, 'reopen'])->name('campaigns.reopen');
        Route::post('campaigns/{campaign}/complete', [CampaignController::class, 'complete'])->name('campaigns.complete');
        Route::post('campaigns/{campaign}/cancel', [CampaignController::class, 'cancel'])->name('campaigns.cancel');
        Route::get('campaigns/{campaign}/collaborations', [CampaignCollaborationController::class, 'index'])->name('campaigns.collaborations.index');
        Route::post('campaigns/{campaign}/invites', [CampaignCollaborationController::class, 'store'])->name('campaigns.invites.store');
        Route::post('campaigns/{campaign}/sourcing', [CampaignCollaborationController::class, 'source'])->name('campaigns.sourcing.store');
        Route::get('collaborations', [CompanyCollaborationController::class, 'index'])->name('collaborations.index');
        Route::get('collaborations/{collaboration}', [CompanyCollaborationController::class, 'show'])->name('collaborations.show');
        Route::get('collaborations/{collaboration}/events', [CompanyCollaborationController::class, 'events'])->name('collaborations.events');
        Route::get('collaborations/{collaboration}/contract', [CompanyCollaborationController::class, 'contract'])->name('collaborations.contract');
        Route::post('collaborations/{collaboration}/select', [CollaborationActionController::class, 'select'])->name('collaborations.select');
        Route::post('collaborations/{collaboration}/book', [CollaborationActionController::class, 'book'])->name('collaborations.book');
        Route::post('collaborations/{collaboration}/cancel', [CollaborationActionController::class, 'cancel'])->name('collaborations.cancel');
        Route::post('collaborations/{collaboration}/follow-ups', [CollaborationActionController::class, 'followUp'])->name('collaborations.follow-ups.store');
        Route::get('collaborations/{collaboration}/messages', [CompanyCollaborationMessageController::class, 'index'])->name('collaborations.messages.index');
        Route::post('collaborations/{collaboration}/messages', [CompanyCollaborationMessageController::class, 'store'])->name('collaborations.messages.store');
        Route::post('collaborations/{collaboration}/messages/read', [CompanyCollaborationMessageController::class, 'read'])->name('collaborations.messages.read');
        Route::get('collaborations/{collaboration}/posts', [CompanyPostController::class, 'index'])->name('collaborations.posts.index');
        Route::get('posts/{post}', [CompanyPostController::class, 'show'])->name('posts.show');
        Route::post('posts/{post}/approve', [CompanyPostController::class, 'approve'])->name('posts.approve');
        Route::post('posts/{post}/changes', [CompanyPostController::class, 'changes'])->name('posts.changes');
        Route::post('posts/{post}/reject', [CompanyPostController::class, 'reject'])->name('posts.reject');
        Route::get('campaigns/{campaign}/tracking-links', [CampaignTrackingLinkController::class, 'index'])->name('campaigns.tracking-links.index');
        Route::post('campaigns/{campaign}/tracking-links', [CampaignTrackingLinkController::class, 'store'])->name('campaigns.tracking-links.store');
        Route::patch('tracking-links/{trackingLink}', [TrackingLinkController::class, 'update'])->name('tracking-links.update');
        Route::delete('tracking-links/{trackingLink}', [TrackingLinkController::class, 'destroy'])->name('tracking-links.destroy');
        Route::get('analytics/overview', [CompanyAnalyticsController::class, 'overview'])->name('analytics.overview');
        Route::get('campaigns/{campaign}/analytics', [CampaignAnalyticsController::class, 'show'])->name('campaigns.analytics.show');
        Route::get('campaigns/{campaign}/analytics/creators', [CampaignAnalyticsController::class, 'creators'])->name('campaigns.analytics.creators');
        Route::get('campaigns/{campaign}/leads', [CampaignLeadController::class, 'index'])->name('campaigns.leads.index');
        Route::post('campaigns/{campaign}/leads', [CampaignLeadController::class, 'store'])->name('campaigns.leads.store');
        Route::patch('leads/{lead}', [CompanyLeadController::class, 'update'])->name('leads.update');
        Route::get('posts/{post}/metrics', [CompanyPostMetricController::class, 'show'])->name('posts.metrics.show');
        Route::get('reports/campaigns/{campaign}', [CampaignReportController::class, 'show'])->name('reports.campaigns.show');
        Route::apiResource('creators', CreatorController::class)
            ->only(['index', 'show'])
            ->parameters(['creators' => 'creatorProfile']);
    });

    Route::middleware(['verified', 'role:creator', 'onboarded'])->prefix('creator')->name('creator.')->group(function () {
        Route::get('ping', [UserController::class, 'show'])->name('ping');
        Route::apiSingleton('profile', CreatorProfileController::class)->only(['show', 'update']);
        Route::apiSingleton('niches', CreatorNicheController::class)->only(['update']);
        Route::apiSingleton('audience', CreatorAudienceController::class, ['creatable' => true])->only(['show', 'store']);
        Route::apiSingleton('billing', BillingController::class)->only(['show']);
        Route::delete('account', [CreatorAccountController::class, 'destroy'])->name('account.destroy');
        Route::get('wallet', [CreatorWalletController::class, 'show'])->name('wallet.show');
        Route::get('wallet/transactions', [CreatorWalletController::class, 'transactions'])->name('wallet.transactions');
        Route::post('wallet/withdrawals', [CreatorWalletController::class, 'storeWithdrawal'])->name('wallet.withdrawals.store');
        Route::get('connect', [ConnectController::class, 'show'])->name('connect.show');
        Route::post('connect/onboarding', [ConnectController::class, 'onboarding'])->name('connect.onboarding');
        Route::post('connect/dashboard', [ConnectController::class, 'dashboard'])->name('connect.dashboard');
        Route::get('payouts', [PayoutController::class, 'index'])->name('payouts.index');
        Route::get('payouts/{payout}', [PayoutController::class, 'show'])->name('payouts.show');
        Route::get('opportunities', [OpportunityController::class, 'index'])->name('opportunities.index');
        Route::get('opportunities/{campaign}', [OpportunityController::class, 'show'])->name('opportunities.show');
        Route::post('opportunities/{campaign}/apply', [OpportunityController::class, 'apply'])->name('opportunities.apply');
        Route::get('collaborations', [CreatorCollaborationController::class, 'index'])->name('collaborations.index');
        Route::get('collaborations/{collaboration}', [CreatorCollaborationController::class, 'show'])->name('collaborations.show');
        Route::post('collaborations/{collaboration}/accept', [CreatorCollaborationController::class, 'accept'])->name('collaborations.accept');
        Route::post('collaborations/{collaboration}/decline', [CreatorCollaborationController::class, 'decline'])->name('collaborations.decline');
        Route::get('collaborations/{collaboration}/messages', [CreatorCollaborationMessageController::class, 'index'])->name('collaborations.messages.index');
        Route::post('collaborations/{collaboration}/messages', [CreatorCollaborationMessageController::class, 'store'])->name('collaborations.messages.store');
        Route::post('collaborations/{collaboration}/messages/read', [CreatorCollaborationMessageController::class, 'read'])->name('collaborations.messages.read');
        Route::get('collaborations/{collaboration}/contract', [CreatorCollaborationController::class, 'contract'])->name('collaborations.contract');
        Route::get('collaborations/{collaboration}/posts', [CreatorPostController::class, 'index'])->name('collaborations.posts.index');
        Route::post('collaborations/{collaboration}/posts', [CreatorPostController::class, 'store'])->name('collaborations.posts.store');
        Route::patch('posts/{post}', [CreatorPostController::class, 'update'])->name('posts.update');
        Route::post('posts/{post}/submit', [CreatorPostController::class, 'submit'])->name('posts.submit');
        Route::post('posts/{post}/schedule', [CreatorPostController::class, 'schedule'])->name('posts.schedule');
        Route::post('posts/{post}/publish', [CreatorPostController::class, 'publish'])->name('posts.publish');
        Route::get('analytics/overview', [CreatorAnalyticsController::class, 'overview'])->name('analytics.overview');
        Route::get('collaborations/{collaboration}/metrics', [CollaborationMetricController::class, 'show'])->name('collaborations.metrics.show');
        Route::get('posts/{post}/metrics', [CreatorPostMetricController::class, 'show'])->name('posts.metrics.show');
    });
});
