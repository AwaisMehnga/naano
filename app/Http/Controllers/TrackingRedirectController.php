<?php

namespace App\Http\Controllers;

use App\Services\TrackingLinkService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TrackingRedirectController extends Controller
{
    public function __construct(private TrackingLinkService $tracking) {}

    public function show(Request $request, string $slug): RedirectResponse
    {
        return $this->tracking->redirect($request, $slug);
    }
}
