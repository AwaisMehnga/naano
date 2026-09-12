<?php

use App\Http\Controllers\Api\Company\AudienceController as CompanyAudienceController;
use App\Http\Controllers\Api\Company\CampaignCollaborationController;
use App\Http\Controllers\Api\Company\CampaignController;
use App\Http\Controllers\Api\Company\CollaborationActionController;
use App\Http\Controllers\Api\Company\CreatorController;
use App\Http\Controllers\Api\Company\IcpController;
use App\Http\Controllers\Api\Company\MemberController;
use App\Http\Controllers\Api\Company\ProfileController as CompanyProfileController;
use App\Http\Controllers\Api\Company\WorkspaceController;
use App\Http\Controllers\Api\Creator\AccountController;
use App\Http\Controllers\Api\Creator\AudienceController as CreatorAudienceController;
use App\Http\Controllers\Api\Creator\BillingController;
use App\Http\Controllers\Api\Creator\NicheController as CreatorNicheController;
use App\Http\Controllers\Api\Creator\ProfileController as CreatorProfileController;
use App\Http\Controllers\Api\NicheController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::apiSingleton('user', UserController::class)->only(['show'])->names(['show' => 'user']);
    Route::apiResource('niches', NicheController::class)->only(['index']);

    Route::middleware(['verified', 'role:company', 'onboarded', 'current.company'])->prefix('company')->name('company.')->group(function () {
        Route::get('ping', [UserController::class, 'show'])->name('ping');
        Route::apiResource('workspaces', WorkspaceController::class)->only(['index', 'update']);
        Route::apiSingleton('profile', CompanyProfileController::class)->only(['show', 'update']);
        Route::apiSingleton('audience', CompanyAudienceController::class)->only(['show', 'update']);
        Route::apiResource('icps', IcpController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::apiResource('members', MemberController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::apiResource('campaigns', CampaignController::class)->only(['index', 'store', 'show', 'update']);
        Route::post('campaigns/{campaign}/launch', [CampaignController::class, 'launch'])->name('campaigns.launch');
        Route::post('campaigns/{campaign}/pause', [CampaignController::class, 'pause'])->name('campaigns.pause');
        Route::post('campaigns/{campaign}/resume', [CampaignController::class, 'resume'])->name('campaigns.resume');
        Route::post('campaigns/{campaign}/reopen', [CampaignController::class, 'reopen'])->name('campaigns.reopen');
        Route::post('campaigns/{campaign}/complete', [CampaignController::class, 'complete'])->name('campaigns.complete');
        Route::post('campaigns/{campaign}/cancel', [CampaignController::class, 'cancel'])->name('campaigns.cancel');
        Route::get('campaigns/{campaign}/collaborations', [CampaignCollaborationController::class, 'index'])->name('campaigns.collaborations.index');
        Route::post('campaigns/{campaign}/invites', [CampaignCollaborationController::class, 'store'])->name('campaigns.invites.store');
        Route::post('collaborations/{collaboration}/select', [CollaborationActionController::class, 'select'])->name('collaborations.select');
        Route::post('collaborations/{collaboration}/cancel', [CollaborationActionController::class, 'cancel'])->name('collaborations.cancel');
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
        Route::apiSingleton('account', AccountController::class, ['destroyable' => true])->only(['destroy']);
    });
});
