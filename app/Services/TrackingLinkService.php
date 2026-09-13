<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\Collaboration;
use App\Models\Company;
use App\Models\Post;
use App\Models\PostMetric;
use App\Models\TrackingClick;
use App\Models\TrackingLink;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Cookie;

class TrackingLinkService
{
    /**
     * @return array<string, mixed>
     */
    public function createHireLink(Company $company, Collaboration $collaboration, Post $post): array
    {
        $website = $company->website;

        if (! is_string($website) || trim($website) === '') {
            throw ValidationException::withMessages([
                'website' => 'Add a company website before booking. Tracking links need a destination.',
            ]);
        }

        $link = TrackingLink::query()->create([
            'post_id' => $post->id,
            'collaboration_id' => $collaboration->id,
            'destination_url' => $website,
            'utm_source' => 'naano',
            'utm_medium' => 'linkedin',
            'utm_campaign' => (string) $collaboration->campaign_id,
            'utm_content' => (string) $collaboration->id,
            'slug' => $this->uniqueSlug(),
        ]);

        return $this->payload($link);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function index(Company $company, Campaign $campaign): array
    {
        $this->ensureCampaignOwned($company, $campaign);

        return array_values(TrackingLink::query()
            ->whereHas('collaboration', fn ($query) => $query->where('campaign_id', $campaign->id))
            ->orderBy('id')
            ->get()
            ->map(fn (TrackingLink $link): array => $this->payload($link))
            ->all());
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function store(Company $company, Campaign $campaign, array $data): array
    {
        $this->ensureCampaignOwned($company, $campaign);

        $collaboration = Collaboration::query()->find((int) $data['collaboration_id']);

        if (! $collaboration instanceof Collaboration || $collaboration->campaign_id !== $campaign->id) {
            abort(404);
        }

        $postId = isset($data['post_id']) ? (int) $data['post_id'] : $collaboration->posts()->orderBy('id')->value('id');

        $link = TrackingLink::query()->create([
            'post_id' => $postId,
            'collaboration_id' => $collaboration->id,
            'destination_url' => $data['destination_url'],
            'utm_source' => $data['utm_source'] ?? 'naano',
            'utm_medium' => $data['utm_medium'] ?? 'linkedin',
            'utm_campaign' => $data['utm_campaign'] ?? (string) $campaign->id,
            'utm_content' => $data['utm_content'] ?? (string) $collaboration->id,
            'slug' => $this->uniqueSlug(),
        ]);

        return $this->payload($link);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function update(Company $company, TrackingLink $link, array $data): array
    {
        $this->ensureLinkOwned($company, $link);

        $link->fill(array_filter([
            'destination_url' => $data['destination_url'] ?? null,
            'utm_source' => $data['utm_source'] ?? null,
            'utm_medium' => $data['utm_medium'] ?? null,
            'utm_campaign' => $data['utm_campaign'] ?? null,
            'utm_content' => $data['utm_content'] ?? null,
            'post_id' => array_key_exists('post_id', $data) ? $data['post_id'] : null,
        ], fn (mixed $value): bool => $value !== null));
        $link->save();

        return $this->payload($link->fresh());
    }

    public function destroy(Company $company, TrackingLink $link): void
    {
        $this->ensureLinkOwned($company, $link);
        $link->delete();
    }

    public function redirect(Request $request, string $slug): RedirectResponse
    {
        $link = TrackingLink::query()->where('slug', $slug)->first();

        if (! $link instanceof TrackingLink) {
            abort(404);
        }

        $visitorKey = $this->visitorKey($request);
        $isUnique = $link->post_id !== null
            && ! TrackingClick::query()
                ->where('post_id', $link->post_id)
                ->where('visitor_key', $visitorKey)
                ->exists();

        TrackingClick::query()->create([
            'tracking_link_id' => $link->id,
            'post_id' => $link->post_id,
            'visitor_key' => $visitorKey,
            'ip_hash' => $this->ipHash($request),
            'user_agent' => Str::limit((string) $request->userAgent(), 255, ''),
            'occurred_at' => now(),
        ]);

        if ($link->post_id !== null) {
            $metrics = $this->metricsFor($link->post_id);
            $metrics->increment('clicks');

            if ($isUnique) {
                $metrics->increment('unique_clicks');
            }

            $metrics->forceFill(['captured_at' => now()])->save();
        }

        return redirect()->away($this->destination($link))->withCookie($this->visitorCookie($visitorKey));
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(TrackingLink $link): array
    {
        return [
            'id' => $link->id,
            'post_id' => $link->post_id,
            'collaboration_id' => $link->collaboration_id,
            'destination_url' => $link->destination_url,
            'utm_source' => $link->utm_source,
            'utm_medium' => $link->utm_medium,
            'utm_campaign' => $link->utm_campaign,
            'utm_content' => $link->utm_content,
            'slug' => $link->slug,
            'short_url' => rtrim((string) config('app.url'), '/').'/t/'.$link->slug,
        ];
    }

    private function uniqueSlug(): string
    {
        do {
            $slug = Str::lower(Str::random(10));
        } while (TrackingLink::withTrashed()->where('slug', $slug)->exists());

        return $slug;
    }

    private function destination(TrackingLink $link): string
    {
        $parts = parse_url($link->destination_url);

        if ($parts === false || ! isset($parts['host'])) {
            return $link->destination_url;
        }

        parse_str($parts['query'] ?? '', $query);

        foreach (['utm_source', 'utm_medium', 'utm_campaign', 'utm_content'] as $key) {
            $value = $link->{$key};

            if (is_string($value) && $value !== '') {
                $query[$key] = $value;
            }
        }

        $scheme = $parts['scheme'] ?? 'https';
        $url = $scheme.'://'.$parts['host'];

        if (isset($parts['port'])) {
            $url .= ':'.$parts['port'];
        }

        $url .= $parts['path'] ?? '';

        if ($query !== []) {
            $url .= '?'.http_build_query($query);
        }

        if (isset($parts['fragment'])) {
            $url .= '#'.$parts['fragment'];
        }

        return $url;
    }

    private function ensureCampaignOwned(Company $company, Campaign $campaign): void
    {
        if ($campaign->company_id !== $company->id) {
            abort(404);
        }
    }

    private function ensureLinkOwned(Company $company, TrackingLink $link): void
    {
        $link->loadMissing('collaboration.campaign');

        if ($link->collaboration->campaign->company_id !== $company->id) {
            abort(404);
        }
    }

    public function metricsFor(int $postId): PostMetric
    {
        return PostMetric::query()->firstOrCreate(
            ['post_id' => $postId],
            [
                'impressions' => 0,
                'likes' => 0,
                'comments' => 0,
                'clicks' => 0,
                'unique_clicks' => 0,
                'qualified_clicks' => 0,
                'leads_count' => 0,
                'captured_at' => now(),
            ],
        );
    }

    private function visitorKey(Request $request): string
    {
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

    private function ipHash(Request $request): ?string
    {
        $ip = $request->ip();

        if (! is_string($ip) || $ip === '') {
            return null;
        }

        return hash('sha256', $ip.config('app.key'));
    }
}
