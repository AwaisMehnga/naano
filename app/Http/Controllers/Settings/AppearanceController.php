<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AppearanceController extends Controller
{
    /**
     * Show the appearance settings page.
     */
    public function edit(): View
    {
        return view('settings.appearance');
    }

    /**
     * Persist the appearance preference.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'appearance' => ['required', 'in:light,dark,system'],
        ]);

        cookie()->queue(cookie()->forever('appearance', $validated['appearance']));

        return to_route('appearance.edit')->with('status', __('Appearance updated.'));
    }
}
