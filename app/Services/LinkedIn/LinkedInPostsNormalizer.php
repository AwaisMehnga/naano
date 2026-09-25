<?php

namespace App\Services\LinkedIn;

use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

class LinkedInPostsNormalizer
{
    /**
     * @param  list<array<string, mixed>>  $rawItems
     * @return list<array{
     *     id: string,
     *     linkedin_url: string|null,
     *     content: string|null,
     *     posted_at: string|null,
     *     likes: int,
     *     comments: int,
     *     shares: int,
     *     reactions: list<array{type: string, count: int}>,
     *     commenters: list<array{name: string|null, headline: string|null, profile_url: string|null}>
     * }>
     */
    public function normalize(array $rawItems, int $maxPosts = 50): array
    {
        $posts = [];

        foreach ($rawItems as $raw) {
            if (! is_array($raw)) {
                continue;
            }

            // harvestapi may wrap the post under "post" or return the post at the root.
            $item = is_array(Arr::get($raw, 'post')) ? Arr::get($raw, 'post') : $raw;

            if (! is_array($item)) {
                continue;
            }

            $id = $this->stringOrNull(
                Arr::get($item, 'id')
                ?? Arr::get($item, 'urn')
                ?? Arr::get($item, 'activityId')
                ?? Arr::get($raw, 'id')
            );

            $url = $this->stringOrNull(
                Arr::get($item, 'linkedinUrl')
                ?? Arr::get($item, 'url')
                ?? Arr::get($item, 'shareUrl')
                ?? Arr::get($raw, 'linkedinUrl')
            );

            if ($id === null && $url === null) {
                continue;
            }

            $engagement = Arr::get($item, 'engagement') ?? Arr::get($raw, 'engagement') ?? [];
            $likes = $this->intOrZero(Arr::get($engagement, 'likes') ?? Arr::get($item, 'likesCount') ?? Arr::get($item, 'numLikes'));
            $comments = $this->intOrZero(Arr::get($engagement, 'comments') ?? Arr::get($item, 'commentsCount') ?? Arr::get($item, 'numComments'));
            $shares = $this->intOrZero(Arr::get($engagement, 'shares') ?? Arr::get($engagement, 'reposts') ?? Arr::get($item, 'repostsCount'));

            $posts[] = [
                'id' => $id ?? md5((string) $url),
                'linkedin_url' => $url,
                'content' => $this->stringOrNull(
                    Arr::get($item, 'content')
                    ?? Arr::get($item, 'text')
                    ?? Arr::get($item, 'commentary')
                ),
                'posted_at' => $this->dateOrNull(
                    Arr::get($item, 'postedAt')
                    ?? Arr::get($item, 'posted_at')
                    ?? Arr::get($item, 'publishedAt')
                    ?? Arr::get($item, 'createdAt')
                ),
                'likes' => $likes,
                'comments' => $comments,
                'shares' => $shares,
                'reactions' => $this->reactions($item, $raw, $likes),
                'commenters' => $this->commenters($raw),
            ];

            if (count($posts) >= $maxPosts) {
                break;
            }
        }

        return $posts;
    }

    /**
     * Build engagers summary from normalized posts' commenters.
     *
     * @param  list<array<string, mixed>>  $posts
     * @return array{people_count: int, reply_rate: float|null, seniority: list<array{label: string, count: int}>, job_title: list<array{label: string, count: int}>, locations: list<array{label: string, count: int}>, top: list<array{name: string|null, headline: string|null, profile_url: string|null}>}|null
     */
    public function engagersSummary(array $posts): ?array
    {
        $people = [];

        foreach ($posts as $post) {
            $commenters = Arr::get($post, 'commenters', []);

            if (! is_array($commenters)) {
                continue;
            }

            foreach ($commenters as $commenter) {
                if (! is_array($commenter)) {
                    continue;
                }

                $key = $this->stringOrNull($commenter['profile_url'] ?? null)
                    ?? $this->stringOrNull($commenter['name'] ?? null);

                if ($key === null) {
                    continue;
                }

                $people[$key] = [
                    'name' => $this->stringOrNull($commenter['name'] ?? null),
                    'headline' => $this->stringOrNull($commenter['headline'] ?? null),
                    'profile_url' => $this->stringOrNull($commenter['profile_url'] ?? null),
                ];
            }
        }

        if ($people === []) {
            return null;
        }

        $seniorityBuckets = [];
        $jobTitleBuckets = [];
        $locationBuckets = [];

        foreach ($people as $person) {
            $headline = $person['headline'] ?? null;
            $seniority = $this->guessSeniority($headline);

            if ($seniority !== null) {
                $seniorityBuckets[$seniority] = ($seniorityBuckets[$seniority] ?? 0) + 1;
            }

            $jobTitle = $this->guessJobTitle($headline);

            if ($jobTitle !== null) {
                $jobTitleBuckets[$jobTitle] = ($jobTitleBuckets[$jobTitle] ?? 0) + 1;
            }

            $location = $this->guessLocation($headline);

            if ($location !== null) {
                $locationBuckets[$location] = ($locationBuckets[$location] ?? 0) + 1;
            }
        }

        $postsWithComments = count(array_filter($posts, fn (array $post): bool => ((int) ($post['comments'] ?? 0)) > 0));
        $replyRate = count($posts) > 0 ? round($postsWithComments / count($posts), 4) : null;

        return [
            'people_count' => count($people),
            'reply_rate' => $replyRate,
            'seniority' => $this->bucketList($seniorityBuckets),
            'job_title' => $this->bucketList($jobTitleBuckets),
            'locations' => $this->bucketList($locationBuckets),
            'top' => array_values(array_slice($people, 0, 12)),
        ];
    }

    /**
     * @param  array<string, mixed>  $item
     * @param  array<string, mixed>  $raw
     * @return list<array{type: string, count: int}>
     */
    private function reactions(array $item, array $raw, int $likesFallback): array
    {
        $rows = Arr::get($item, 'reactions') ?? Arr::get($raw, 'reactions') ?? Arr::get($item, 'reactionTypeCounts') ?? [];

        if (! is_array($rows) || $rows === []) {
            return $likesFallback > 0 ? [['type' => 'LIKE', 'count' => $likesFallback]] : [];
        }

        $normalized = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $type = $this->stringOrNull(Arr::get($row, 'type') ?? Arr::get($row, 'reactionType') ?? Arr::get($row, 'name'));
            $count = $this->intOrZero(Arr::get($row, 'count') ?? Arr::get($row, 'reactionCount'));

            if ($type === null || $count < 1) {
                continue;
            }

            $normalized[] = [
                'type' => strtoupper($type),
                'count' => $count,
            ];
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $raw
     * @return list<array{name: string|null, headline: string|null, profile_url: string|null}>
     */
    private function commenters(array $raw): array
    {
        $comments = Arr::get($raw, 'comments') ?? Arr::get($raw, 'post.comments') ?? [];

        if (! is_array($comments)) {
            return [];
        }

        $people = [];

        foreach (array_slice($comments, 0, 30) as $comment) {
            if (! is_array($comment)) {
                continue;
            }

            $author = Arr::get($comment, 'author') ?? Arr::get($comment, 'actor') ?? $comment;

            if (! is_array($author)) {
                continue;
            }

            $name = $this->stringOrNull(
                Arr::get($author, 'name')
                ?? Arr::get($author, 'fullName')
                ?? trim(((string) Arr::get($author, 'firstName', '')).' '.((string) Arr::get($author, 'lastName', '')))
            );

            $people[] = [
                'name' => $name,
                'headline' => $this->stringOrNull(Arr::get($author, 'headline') ?? Arr::get($author, 'occupation')),
                'profile_url' => $this->stringOrNull(
                    Arr::get($author, 'profileUrl')
                    ?? Arr::get($author, 'linkedinUrl')
                    ?? Arr::get($author, 'url')
                ),
            ];
        }

        return $people;
    }

    /**
     * @param  array<string, int>  $buckets
     * @return list<array{label: string, count: int}>
     */
    private function bucketList(array $buckets): array
    {
        arsort($buckets);

        $list = [];

        foreach ($buckets as $label => $count) {
            $list[] = ['label' => (string) $label, 'count' => (int) $count];
        }

        return array_slice($list, 0, 8);
    }

    private function guessSeniority(?string $headline): ?string
    {
        if ($headline === null) {
            return null;
        }

        $haystack = strtolower($headline);

        return match (true) {
            str_contains($haystack, 'ceo') || str_contains($haystack, 'founder') || str_contains($haystack, 'co-founder') || str_contains($haystack, 'cfo') || str_contains($haystack, 'cto') || str_contains($haystack, 'coo') => 'Founder / C-level',
            str_contains($haystack, 'vp') || str_contains($haystack, 'vice president') || str_contains($haystack, 'director') => 'Director / VP',
            str_contains($haystack, 'head of') || str_contains($haystack, 'manager') || str_contains($haystack, 'lead') => 'Manager / Lead',
            str_contains($haystack, 'senior') || str_contains($haystack, 'principal') => 'Senior IC',
            default => 'Other',
        };
    }

    private function guessJobTitle(?string $headline): ?string
    {
        if ($headline === null) {
            return null;
        }

        $haystack = strtolower($headline);

        return match (true) {
            str_contains($haystack, 'founder') || str_contains($haystack, 'co-founder') || str_contains($haystack, 'ceo') => 'Founders',
            str_contains($haystack, 'marketing') || str_contains($haystack, 'growth') || str_contains($haystack, 'demand gen') || str_contains($haystack, 'brand') => 'Marketing',
            str_contains($haystack, 'sales') || str_contains($haystack, 'account executive') || str_contains($haystack, 'business development') || str_contains($haystack, 'gtm') => 'Sales',
            str_contains($haystack, 'product') || str_contains($haystack, 'pm ') => 'Product',
            str_contains($haystack, 'engineer') || str_contains($haystack, 'developer') || str_contains($haystack, 'cto') || str_contains($haystack, 'software') => 'Engineering',
            str_contains($haystack, 'design') || str_contains($haystack, 'ux') || str_contains($haystack, 'ui') => 'Design',
            str_contains($haystack, 'ops') || str_contains($haystack, 'operations') || str_contains($haystack, 'people') || str_contains($haystack, 'hr') => 'Operations',
            default => 'Other',
        };
    }

    private function guessLocation(?string $headline): ?string
    {
        if ($headline === null) {
            return null;
        }

        // Common LinkedIn headline tails: "… | Paris" / "… · London, UK" / "… — Berlin"
        if (preg_match('/(?:\||·|—|-)\s*([A-Za-z][A-Za-z\s\-]{1,40})(?:,\s*([A-Za-z]{2,}))?\s*$/u', $headline, $match) !== 1) {
            return null;
        }

        $city = trim($match[1]);
        $country = isset($match[2]) ? trim($match[2]) : null;

        if ($city === '' || str_contains(strtolower($city), 'http')) {
            return null;
        }

        $noise = ['remote', 'worldwide', 'global', 'open to work', 'hiring'];

        foreach ($noise as $word) {
            if (str_contains(strtolower($city), $word)) {
                return null;
            }
        }

        return $country !== null && $country !== '' ? $city.', '.$country : $city;
    }

    private function stringOrNull(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function intOrZero(mixed $value): int
    {
        if (is_int($value)) {
            return max(0, $value);
        }

        if (is_string($value) && is_numeric($value)) {
            return max(0, (int) $value);
        }

        if (is_float($value)) {
            return max(0, (int) $value);
        }

        return 0;
    }

    private function dateOrNull(mixed $value): ?string
    {
        if (is_numeric($value)) {
            try {
                return Carbon::createFromTimestamp((int) $value)->toIso8601String();
            } catch (\Throwable) {
                return null;
            }
        }

        $asString = $this->stringOrNull($value);

        if ($asString === null) {
            return null;
        }

        try {
            return Carbon::parse($asString)->toIso8601String();
        } catch (\Throwable) {
            return $asString;
        }
    }
}
