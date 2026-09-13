<?php

use App\Models\TrackingClick;
use App\Models\TrackingEvent;

beforeEach(function () {
    $this->disableCookieEncryption();
});

test('the same visitor five hops counts five total clicks and one unique', function () {
    [, $collaboration] = bookedDeal();
    $link = $collaboration->trackingLinks()->first();
    $post = $collaboration->posts()->first();

    $first = $this->get(route('tracking.redirect', $link->slug))
        ->assertRedirect()
        ->assertPlainCookie('naano_vid');

    $visitor = $first->getCookie('naano_vid', false)?->getValue();

    expect($visitor)->not->toBeEmpty();

    foreach (range(1, 4) as $ignored) {
        $this->withUnencryptedCookie('naano_vid', $visitor)
            ->get(route('tracking.redirect', $link->slug))
            ->assertRedirect();
    }

    $metrics = $post->metrics()->first();

    expect($metrics->clicks)->toBe(5)
        ->and($metrics->unique_clicks)->toBe(1)
        ->and(TrackingClick::query()->where('post_id', $post->id)->count())->toBe(5);
});

test('a hop does not qualify the visit', function () {
    [, $collaboration] = bookedDeal();
    $link = $collaboration->trackingLinks()->first();
    $post = $collaboration->posts()->first();

    $this->get(route('tracking.redirect', $link->slug))->assertRedirect();

    expect($post->metrics()->first()->qualified_clicks)->toBe(0);
});

test('qualify events without a cookie count once for the same visitor key', function () {
    [, $collaboration] = bookedDeal();
    $link = $collaboration->trackingLinks()->first();
    $post = $collaboration->posts()->first();
    $visitor = fake()->uuid();

    $this->withHeaders(['Origin' => 'https://example.com'])
        ->postJson(route('api.tracking.events', $link->slug), [
            'type' => 'qualify',
            'visitor_key' => $visitor,
        ])
        ->assertOk();

    $this->withHeaders(['Origin' => 'https://example.com'])
        ->postJson(route('api.tracking.events', $link->slug), [
            'type' => 'qualify',
            'visitor_key' => $visitor,
        ])
        ->assertOk();

    expect($post->metrics()->first()->fresh()->qualified_clicks)->toBe(1);
    expect(TrackingEvent::query()->where('tracking_link_id', $link->id)->where('type', 'qualify')->count())->toBe(1);
});

test('a qualify event counts once per visitor', function () {
    [, $collaboration] = bookedDeal();
    $link = $collaboration->trackingLinks()->first();
    $post = $collaboration->posts()->first();

    $this->get(route('tracking.redirect', $link->slug))->assertRedirect();
    $visitor = TrackingClick::query()->where('tracking_link_id', $link->id)->value('visitor_key');

    $this->withUnencryptedCookie('naano_vid', $visitor)
        ->withHeaders(['Origin' => 'https://example.com'])
        ->postJson(route('api.tracking.events', $link->slug), [
            'type' => 'qualify',
            'visitor_key' => $visitor,
        ])
        ->assertOk();

    $this->withUnencryptedCookie('naano_vid', $visitor)
        ->withHeaders(['Origin' => 'https://example.com'])
        ->postJson(route('api.tracking.events', $link->slug), [
            'type' => 'qualify',
            'visitor_key' => $visitor,
        ])
        ->assertOk();

    expect($post->metrics()->first()->fresh()->qualified_clicks)->toBe(1)
        ->and($post->metrics()->first()->clicks)->toBe(1);
});

test('a form event creates an attributed lead', function () {
    [, $collaboration] = bookedDeal();
    $link = $collaboration->trackingLinks()->first();
    $post = $collaboration->posts()->first();

    $this->get(route('tracking.redirect', $link->slug))->assertRedirect();
    $visitor = TrackingClick::query()->where('tracking_link_id', $link->id)->value('visitor_key');

    $this->withUnencryptedCookie('naano_vid', $visitor)
        ->withHeaders(['Origin' => 'https://example.com'])
        ->postJson(route('api.tracking.events', $link->slug), [
            'type' => 'lead',
            'visitor_key' => $visitor,
            'payload' => ['form' => 'signup'],
        ])
        ->assertOk();

    $this->assertDatabaseHas('leads', [
        'campaign_id' => $collaboration->campaign_id,
        'post_id' => $post->id,
        'tracking_link_id' => $link->id,
        'source' => 'form',
    ]);

    expect($post->metrics()->first()->fresh()->leads_count)->toBe(1);
});

test('pixel events from the wrong origin are forbidden', function () {
    [, $collaboration] = bookedDeal();
    $link = $collaboration->trackingLinks()->first();

    $this->withHeaders(['Origin' => 'https://evil.test'])
        ->postJson(route('api.tracking.events', $link->slug), ['type' => 'qualify'])
        ->assertForbidden();
});

test('pixel event preflight echoes the page origin for credentialed requests', function () {
    [, $collaboration] = bookedDeal();
    $link = $collaboration->trackingLinks()->first();

    $this->withHeaders([
        'Origin' => 'https://example.com',
        'Access-Control-Request-Method' => 'POST',
        'Access-Control-Request-Headers' => 'content-type,accept',
    ])->options(route('api.tracking.events', $link->slug))
        ->assertNoContent()
        ->assertHeader('Access-Control-Allow-Origin', 'https://example.com')
        ->assertHeader('Access-Control-Allow-Credentials', 'true');
});

test('pixel events from a local preview origin are recorded', function () {
    [, $collaboration] = bookedDeal();
    $link = $collaboration->trackingLinks()->first();

    $this->withHeaders(['Origin' => 'http://127.0.0.1:5500'])
        ->postJson(route('api.tracking.events', $link->slug), ['type' => 'qualify'])
        ->assertOk()
        ->assertHeader('Access-Control-Allow-Origin', 'http://127.0.0.1:5500')
        ->assertHeader('Access-Control-Allow-Credentials', 'true');
});

test('the pixel script is public', function () {
    $this->get(route('tracking.pixel'))
        ->assertOk()
        ->assertHeader('content-type', 'application/javascript; charset=UTF-8')
        ->assertSee('localStorage.getItem("naano_vid")', false)
        ->assertSee('visitor_key', false)
        ->assertSee('window.__naanoPixel', false);
});
