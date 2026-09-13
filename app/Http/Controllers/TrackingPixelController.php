<?php

namespace App\Http\Controllers;

use App\Services\TrackingPixelService;
use Illuminate\Http\Response;

class TrackingPixelController extends Controller
{
    public function __construct(private TrackingPixelService $pixel) {}

    public function show(): Response
    {
        return response($this->pixel->script(), 200, [
            'Content-Type' => 'application/javascript; charset=UTF-8',
            'Cache-Control' => 'public, max-age=300',
        ]);
    }
}
