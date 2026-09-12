<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\EmailCodeService;
use App\Support\HomeRedirect;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class EmailCodeController extends Controller
{
    /**
     * Verify the emailed six-digit code.
     */
    public function store(Request $request, EmailCodeService $codes): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        $user = $request->user();

        if (! $user instanceof User) {
            return redirect()->route('login');
        }

        if ($user->hasVerifiedEmail()) {
            return redirect(HomeRedirect::path($user));
        }

        if (! $codes->verify($user, $request->string('code')->toString())) {
            throw ValidationException::withMessages([
                'code' => 'That code is incorrect or has expired.',
            ]);
        }

        return redirect()->intended(HomeRedirect::path($user).'?verified=1');
    }
}
