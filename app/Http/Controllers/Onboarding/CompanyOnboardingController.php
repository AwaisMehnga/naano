<?php

namespace App\Http\Controllers\Onboarding;

use App\Http\Controllers\Controller;
use App\Http\Requests\Onboarding\StoreCompanyBriefRequest;
use App\Http\Requests\Onboarding\StoreCompanyWebsiteRequest;
use App\Services\CompanyBriefService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CompanyOnboardingController extends Controller
{
    public function __construct(private CompanyBriefService $briefs) {}

    /**
     * Show the current company onboarding step.
     */
    public function show(Request $request): View
    {
        $user = $request->user();
        $company = $this->briefs->company($user);

        return view('onboarding.company.show', [
            'user' => $user,
            'company' => $company,
            'step' => $this->briefs->step($company),
        ]);
    }

    /**
     * Analyze the company website and draft a brief.
     */
    public function website(StoreCompanyWebsiteRequest $request): JsonResponse|RedirectResponse
    {
        $result = $this->briefs->analyze(
            $this->briefs->company($request->user()),
            $request->validated('website'),
        );

        $message = $result['analyzed']
            ? 'OK'
            : 'We could not analyze the site. Add your brief below.';

        return $this->onboardingDone($request, route('onboarding.company'), $message);
    }

    /**
     * Save the reviewed brief and open the company workspace.
     */
    public function brief(StoreCompanyBriefRequest $request): JsonResponse|RedirectResponse
    {
        $company = $this->briefs->company($request->user());

        if ($this->briefs->step($company) !== 'brief') {
            return $this->onboardingDone($request, route('onboarding.company'));
        }

        $this->briefs->saveBrief(
            $company,
            $request->safe()->only(['value_proposition', 'icps']),
        );

        return $this->onboardingDone($request, route('company'));
    }
}
