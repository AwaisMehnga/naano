<?php

namespace App\Http\Controllers\Onboarding;

use App\Http\Controllers\Controller;
use App\Http\Requests\Onboarding\StoreCreatorIndustriesRequest;
use App\Http\Requests\Onboarding\StoreCreatorLinkedInRequest;
use App\Http\Requests\Onboarding\StoreCreatorOfferRequest;
use App\Models\User;
use App\Services\CreatorOnboardingService;
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
     * Store LinkedIn URL and card fields.
     */
    public function linkedin(StoreCreatorLinkedInRequest $request): JsonResponse|RedirectResponse
    {
        $this->onboarding->saveLinkedIn(
            $this->onboarding->profile($request->user()),
            $request->safe()->only(['linkedin_url', 'headline', 'country', 'photo']),
        );

        return $this->onboardingDone($request, route('onboarding.creator'));
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

        if (! $user instanceof User || ! $user->hasRole('creator')) {
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
