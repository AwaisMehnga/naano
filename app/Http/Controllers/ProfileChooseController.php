<?php

namespace App\Http\Controllers;

use App\Enums\ProfileType;
use App\Http\Requests\ChooseProfileRequest;
use App\Models\User;
use App\Services\ActiveProfileService;
use App\Services\ProfileService;
use App\Support\HomeRedirect;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileChooseController extends Controller
{
    public function __construct(
        private ProfileService $profiles,
        private ActiveProfileService $activeProfile,
    ) {}

    public function show(Request $request): View|RedirectResponse
    {
        $user = $this->actor($request);

        if ($this->activeProfile->defaultType($user) instanceof ProfileType) {
            return redirect(HomeRedirect::path($user, $request));
        }

        return view('profiles.choose', [
            'canCreate' => ['creator', 'company'],
        ]);
    }

    public function store(ChooseProfileRequest $request): RedirectResponse
    {
        $user = $this->actor($request);
        $type = $request->enum('type', ProfileType::class);

        if (! $type instanceof ProfileType) {
            return back()->withErrors(['type' => 'Choose a profile type.']);
        }

        if ($type === ProfileType::Creator) {
            $this->profiles->createCreator($user, $request);
        } else {
            $this->profiles->createCompany($user, $request);
        }

        return redirect(HomeRedirect::path($user->fresh(), $request));
    }

    private function actor(Request $request): User
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        return $user;
    }
}
