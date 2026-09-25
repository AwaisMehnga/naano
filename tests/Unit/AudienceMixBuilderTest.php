<?php

use App\Services\LinkedIn\AudienceMixBuilder;
use App\Services\LinkedIn\LinkedInPostsNormalizer;

test('audience mix builder converts engagers buckets to percentages', function () {
    $mix = (new AudienceMixBuilder)->fromEngagers([
        'people_count' => 10,
        'reply_rate' => 0.5,
        'seniority' => [
            ['label' => 'Founder / C-level', 'count' => 5],
            ['label' => 'Director / VP', 'count' => 5],
        ],
        'job_title' => [
            ['label' => 'Founders', 'count' => 6],
            ['label' => 'Marketing', 'count' => 4],
        ],
        'locations' => [
            ['label' => 'Paris', 'count' => 10],
        ],
        'top' => [],
    ]);

    expect($mix['seniority']['Founder / C-level'])->toBe(50)
        ->and($mix['seniority']['Director / VP'])->toBe(50)
        ->and($mix['job_title']['Founders'])->toBe(60)
        ->and($mix['job_title']['Marketing'])->toBe(40)
        ->and($mix['geo']['Paris'])->toBe(100);
});

test('posts normalizer builds seniority job title and location engagers', function () {
    $posts = (new LinkedInPostsNormalizer)->normalize([
        [
            'id' => '1',
            'linkedinUrl' => 'https://www.linkedin.com/posts/1',
            'content' => 'Hello',
            'engagement' => ['likes' => 2, 'comments' => 2, 'shares' => 0],
            'comments' => [
                [
                    'author' => [
                        'name' => 'Ada',
                        'headline' => 'Founder & CEO · Paris, France',
                        'profileUrl' => 'https://www.linkedin.com/in/ada',
                    ],
                ],
                [
                    'author' => [
                        'name' => 'Bob',
                        'headline' => 'Head of Marketing | London',
                        'profileUrl' => 'https://www.linkedin.com/in/bob',
                    ],
                ],
            ],
        ],
    ]);

    $engagers = (new LinkedInPostsNormalizer)->engagersSummary($posts);

    expect($engagers)->not->toBeNull()
        ->and($engagers['people_count'])->toBe(2)
        ->and(collect($engagers['seniority'])->pluck('label')->all())->toContain('Founder / C-level')
        ->and(collect($engagers['job_title'])->pluck('label')->all())->toContain('Founders')
        ->and(collect($engagers['job_title'])->pluck('label')->all())->toContain('Marketing')
        ->and(collect($engagers['locations'])->pluck('label')->all())->not->toBeEmpty();
});
