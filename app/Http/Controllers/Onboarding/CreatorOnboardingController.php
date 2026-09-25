<?php

namespace App\Http\Controllers\Onboarding;

use App\Enums\ProfileType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Onboarding\StoreCreatorIndustriesRequest;
use App\Http\Requests\Onboarding\StoreCreatorLinkedInRequest;
use App\Http\Requests\Onboarding\StoreCreatorOfferRequest;
use App\Models\User;
use App\Services\CreatorOnboardingService;
use App\Support\AjaxResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CreatorOnboardingController extends Controller
{
    public function __construct(private CreatorOnboardingService $onboarding) {}

    /**
     * Show the current creator onboarding step.
     */
    public function show(Request $request): View
    {
        $user = $request->user();
        $profile = $this->onboarding->profile($user);

        return view('onboarding.creator.show', [
            'user' => $user,
            'profile' => $profile,
            'step' => $this->onboarding->step($profile),
            'countries' => config('onboarding.countries'),
            'industries' => config('onboarding.industries'),
        ]);
    }

    /**
     * Save LinkedIn URL and issue an ownership challenge code.
     */
    public function linkedin(StoreCreatorLinkedInRequest $request): JsonResponse|RedirectResponse
    {
        $result = $this->onboarding->startLinkedIn(
            $this->onboarding->profile($request->user()),
            $request->safe()->only(['linkedin_url', 'country', 'photo']),
        );

        if ($request->expectsJson()) {
            return AjaxResponse::success([
                'redirect' => route('onboarding.creator'),
                'verify_code' => $result['verify_code'],
            ], 'Add this code at the end of your LinkedIn headline.');
        }

        return redirect()->route('onboarding.creator');
    }

    /**
     * Scrape LinkedIn and confirm the verify code is in the headline.
     */
    public function linkedinVerify(Request $request): JsonResponse|RedirectResponse
    {
        $user = $request->user();

        if (! $user instanceof User || ! $user->ownsProfile(ProfileType::Creator)) {
            abort(403);
        }

        $this->onboarding->verifyLinkedIn($this->onboarding->profile($user));

        if ($request->expectsJson()) {
            return AjaxResponse::success([
                'redirect' => route('onboarding.creator'),
                'wait_for_posts' => true,
                'status_url' => route('onboarding.creator.linkedin.status'),
            ], 'LinkedIn verified. Syncing posts…');
        }

        return redirect()->route('onboarding.creator');
    }

    /**
     * Poll LinkedIn posts sync during onboarding.
     */
    public function linkedinStatus(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user instanceof User || ! $user->ownsProfile(ProfileType::Creator)) {
            abort(403);
        }

        $profile = $this->onboarding->profile($user)->fresh();
        $posts = is_array($profile->linkedin_posts) ? $profile->linkedin_posts : [];

        return AjaxResponse::success([
            'verified' => $profile->isLinkedInVerified(),
            'posts_count' => count($posts),
            'posts_ready' => $posts !== [],
            'synced_at' => $profile->linkedin_synced_at?->toIso8601String(),
        ]);
    }

    /**
     * Store creator industries.
     */
    public function industries(StoreCreatorIndustriesRequest $request): JsonResponse|RedirectResponse
    {
        $profile = $this->onboarding->profile($request->user());

        if (! in_array($this->onboarding->step($profile), ['industries', 'offer', 'professional'], true)) {
            return $this->onboardingDone($request, route('onboarding.creator'));
        }

        $this->onboarding->saveIndustries($profile, $request->validated('industries'));

        return $this->onboardingDone($request, route('onboarding.creator'));
    }

    /**
     * Store per-post price and optional bundles.
     */
    public function offer(StoreCreatorOfferRequest $request): JsonResponse|RedirectResponse
    {
        $profile = $this->onboarding->profile($request->user());

        if (! in_array($this->onboarding->step($profile), ['offer', 'professional'], true)) {
            return $this->onboardingDone($request, route('onboarding.creator'));
        }

        $bundles = collect($request->validated('bundles') ?? [])
            ->map(fn (array $bundle): array => [
                'posts' => (int) $bundle['posts'],
                'total_cents' => (int) round(((float) $bundle['total']) * 100),
            ])
            ->values()
            ->all();

        $this->onboarding->saveOffer($profile, [
            'price_cents' => (int) round(((float) $request->validated('price')) * 100),
            'bundles' => $bundles,
        ]);

        return $this->onboardingDone($request, route('onboarding.creator'));
    }

    /**
     * Skip professional info and open the creator workspace.
     */
    public function complete(Request $request): JsonResponse|RedirectResponse
    {
        $user = $request->user();

        if (! $user instanceof User || ! $user->ownsProfile(ProfileType::Creator)) {
            abort(403);
        }

        $profile = $this->onboarding->profile($user);

        if ($this->onboarding->step($profile) !== 'professional') {
            return $this->onboardingDone($request, route('onboarding.creator'));
        }

        $this->onboarding->complete($profile);

        return $this->onboardingDone($request, route('creator'));
    }
}
