<?php

namespace App\Services;

use App\Enums\LeadSource;
use App\Models\Lead;
use App\Models\TrackingEvent;
use App\Models\TrackingLink;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Cookie;

class TrackingPixelService
{
    public function __construct(private TrackingLinkService $tracking) {}

    public function script(): string
    {
        $origin = rtrim((string) config('app.url'), '/');

        return <<<JS
(function () {
  if (window.__naanoPixel) {
    return;
  }
  window.__naanoPixel = true;
  var script = document.currentScript;
  if (!script) {
    return;
  }
  var params = new URL(script.src, window.location.href).searchParams;
  var slug = script.getAttribute("data-slug") || params.get("s");
  if (!slug) {
    return;
  }
  var endpoint = {$this->jsString($origin)} + "/api/t/" + encodeURIComponent(slug) + "/events";
  function visitorKey() {
    try {
      var key = window.localStorage.getItem("naano_vid");
      if (key) {
        return key;
      }
      if (!crypto.randomUUID) {
        return "";
      }
      key = crypto.randomUUID();
      window.localStorage.setItem("naano_vid", key);
      return key;
    } catch (e) {
      return "";
    }
  }
  var storedVisitor = visitorKey();
  function send(type, payload) {
    var body = { type: type, payload: payload || {} };
    if (storedVisitor) {
      body.visitor_key = storedVisitor;
    }
    fetch(endpoint, {
      method: "POST",
      credentials: "include",
      headers: {
        "Content-Type": "application/json",
        "Accept": "application/json"
      },
      body: JSON.stringify(body)
    }).catch(function () {});
  }
  window.setTimeout(function () {
    send("qualify");
  }, 30000);
  document.addEventListener("submit", function () {
    send("lead", { form: true });
  }, true);
})();
JS;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{payload: array<string, mixed>, cookie: Cookie}
     */
    public function record(Request $request, string $slug, array $data): array
    {
        $link = TrackingLink::query()->where('slug', $slug)->first();

        if (! $link instanceof TrackingLink) {
            abort(404);
        }

        $this->assertOrigin($request, $link);

        $visitorKey = $this->visitorKey($request, $data);
        $type = (string) $data['type'];
        $payload = is_array($data['payload'] ?? null) ? $data['payload'] : [];

        if ($type === 'qualify') {
            $this->qualify($link, $visitorKey, $payload);
        }

        if ($type === 'lead') {
            $this->lead($link, $visitorKey, $payload);
        }

        return [
            'payload' => ['recorded' => true],
            'cookie' => $this->visitorCookie($visitorKey),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function qualify(TrackingLink $link, string $visitorKey, array $payload): void
    {
        $exists = TrackingEvent::query()
            ->where('tracking_link_id', $link->id)
            ->where('visitor_key', $visitorKey)
            ->where('type', 'qualify')
            ->exists();

        if ($exists) {
            return;
        }

        TrackingEvent::query()->create([
            'tracking_link_id' => $link->id,
            'post_id' => $link->post_id,
            'visitor_key' => $visitorKey,
            'type' => 'qualify',
            'payload' => $payload === [] ? null : $payload,
            'occurred_at' => now(),
        ]);

        if ($link->post_id !== null) {
            $metrics = $this->tracking->metricsFor($link->post_id);
            $metrics->increment('qualified_clicks');
            $metrics->forceFill(['captured_at' => now()])->save();
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function lead(TrackingLink $link, string $visitorKey, array $payload): void
    {
        $link->loadMissing('collaboration.campaign');

        TrackingEvent::query()->create([
            'tracking_link_id' => $link->id,
            'post_id' => $link->post_id,
            'visitor_key' => $visitorKey,
            'type' => 'lead',
            'payload' => $payload === [] ? null : $payload,
            'occurred_at' => now(),
        ]);

        Lead::query()->create([
            'company_id' => $link->collaboration->campaign->company_id,
            'campaign_id' => $link->collaboration->campaign_id,
            'post_id' => $link->post_id,
            'tracking_link_id' => $link->id,
            'occurred_at' => now(),
            'source' => LeadSource::Form,
            'payload' => [
                ...$payload,
                'visitor_key' => $visitorKey,
            ],
        ]);

        if ($link->post_id !== null) {
            $metrics = $this->tracking->metricsFor($link->post_id);
            $metrics->increment('leads_count');
            $metrics->forceFill(['captured_at' => now()])->save();
        }
    }

    private function assertOrigin(Request $request, TrackingLink $link): void
    {
        $origin = $request->headers->get('Origin') ?? $request->headers->get('Referer');

        if (! is_string($origin) || $origin === '') {
            abort(403);
        }

        $originHost = $this->host($origin);
        $destinationHost = $this->host($link->destination_url);

        if ($originHost === null || $destinationHost === null) {
            abort(403);
        }

        if ($originHost === $destinationHost || $this->isLocalPreviewHost($originHost)) {
            return;
        }

        abort(403);
    }

    private function host(string $url): ?string
    {
        $host = parse_url($url, PHP_URL_HOST);

        if (! is_string($host) || $host === '') {
            return null;
        }

        return strtolower((string) preg_replace('/^www\./', '', $host));
    }

    private function isLocalPreviewHost(string $host): bool
    {
        return ! app()->isProduction() && in_array($host, ['localhost', '127.0.0.1'], true);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function visitorKey(Request $request, array $data = []): string
    {
        $fromBody = $data['visitor_key'] ?? null;

        if (is_string($fromBody) && $fromBody !== '') {
            return $fromBody;
        }

        $existing = $request->cookies->get('naano_vid');

        if (is_string($existing) && $existing !== '') {
            return $existing;
        }

        return (string) Str::uuid();
    }

    private function visitorCookie(string $visitorKey): Cookie
    {
        return cookie(
            'naano_vid',
            $visitorKey,
            60 * 24 * 365,
            '/',
            null,
            false,
            true,
            false,
            'lax',
        );
    }

    private function jsString(string $value): string
    {
        return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
    }
}
