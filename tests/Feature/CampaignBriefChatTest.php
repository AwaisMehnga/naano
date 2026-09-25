<?php

use App\Ai\Agents\CampaignBriefAgent;
use App\Ai\BriefDocument;
use App\Enums\CampaignStatus;
use App\Models\Campaign;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\Responses\Data\ToolCall;

test('brief document edits one field without rewriting the rest', function () {
    $document = new BriefDocument([
        'context' => 'Grow pipeline',
        'product' => 'Naano',
        'key_message' => 'Old message',
        'audience' => [
            'industries' => 'Software',
            'geographies' => 'Europe',
            'tone' => 'Professional',
        ],
    ]);

    $document->read();
    $document->edit('key_message', 'Old message', 'Book creators without the agency tax.');

    expect($document->snapshot()['key_message'])->toBe('Book creators without the agency tax.')
        ->and($document->snapshot()['context'])->toBe('Grow pipeline')
        ->and($document->snapshot()['product'])->toBe('Naano')
        ->and($document->snapshot()['audience']['tone'])->toBe('Professional')
        ->and($document->patches())->toHaveCount(1)
        ->and($document->patches()[0]['path'])->toBe('key_message');
});

test('brief document refuses edit before read', function () {
    $document = new BriefDocument(['key_message' => 'Hello']);

    expect(fn () => $document->edit('key_message', 'Hello', 'Hi'))
        ->toThrow(InvalidArgumentException::class, 'Call read_brief before edit_brief.');
});

test('companies can chat with the campaign brief agent and receive surgical patches', function () {
    Http::fake([
        'https://example.com' => Http::response('<html><body>Finance software for operators</body></html>'),
    ]);

    $owner = User::factory()->company()->onboarded()->create();
    $owner->company->update([
        'website' => 'https://example.com',
        'value_proposition' => 'We help B2B teams book LinkedIn creators.',
        'icps' => [
            ['title' => 'Demand gen leads', 'description' => 'Pipeline owners'],
        ],
    ]);
    $campaign = Campaign::factory()->create([
        'company_id' => $owner->company->id,
        'created_by_user_id' => $owner->id,
        'status' => CampaignStatus::Draft,
        'name' => 'Visibility push',
        'brief' => [
            'context' => 'Grow LinkedIn pipeline',
            'product' => 'Naano booking workspace',
            'differentiators' => ['Creator marketplace'],
            'target' => 'Demand gen leads',
            'pains' => ['Hard to find vetted creators'],
            'trigger' => 'Launching a campaign',
            'key_message' => 'Old message',
            'audience' => [
                'industries' => 'Software',
                'geographies' => 'Europe',
                'tone' => 'Professional',
            ],
            'editorial' => [
                'do' => ['Show real outcomes'],
                'avoid' => ['Invent metrics'],
            ],
            'references' => [],
            'angles' => [],
        ],
    ]);

    CampaignBriefAgent::fake([
        new ToolCall('call_read', 'read_brief', ['path' => 'key_message']),
        new ToolCall('call_edit', 'edit_brief', [
            'path' => 'key_message',
            'old_value' => 'Old message',
            'new_value' => 'Book creators without the agency tax.',
        ]),
        'Updated the key message. Review the form and save.',
    ]);

    $response = $this->actingAs($owner)
        ->postJson(route('api.company.campaigns.brief.chat', $campaign), [
            'message' => 'Tighten the key message.',
            'brief' => $campaign->brief,
        ])
        ->assertOk()
        ->assertJsonPath('data.reply', 'Updated the key message. Review the form and save.')
        ->assertJsonPath('data.brief.key_message', 'Book creators without the agency tax.')
        ->assertJsonPath('data.brief.context', 'Grow LinkedIn pipeline')
        ->assertJsonPath('data.brief.product', 'Naano booking workspace')
        ->assertJsonPath('data.brief.audience.tone', 'Professional');

    expect($response->json('data.patches'))->toHaveCount(1)
        ->and($response->json('data.patches.0.path'))->toBe('key_message')
        ->and($response->json('data.patches.0.after'))->toBe('Book creators without the agency tax.');

    CampaignBriefAgent::assertPrompted(
        fn ($prompt): bool => str_contains((string) $prompt->prompt, 'Tighten the key message.'),
    );
});

test('other workspaces cannot chat on a campaign brief', function () {
    $owner = User::factory()->company()->onboarded()->create();
    $other = User::factory()->company()->onboarded()->create();
    $campaign = Campaign::factory()->create([
        'company_id' => $owner->company->id,
        'created_by_user_id' => $owner->id,
    ]);

    CampaignBriefAgent::fake(['Nope']);

    $this->actingAs($other)
        ->postJson(route('api.company.campaigns.brief.chat', $campaign), [
            'message' => 'Draft the brief.',
        ])
        ->assertNotFound();
});

test('brief chat accepts drafts with null list items', function () {
    $owner = User::factory()->company()->onboarded()->create();
    $campaign = Campaign::factory()->create([
        'company_id' => $owner->company->id,
        'created_by_user_id' => $owner->id,
        'status' => CampaignStatus::Draft,
        'brief' => [
            'context' => 'Grow pipeline',
            'product' => 'Naano',
            'key_message' => 'Hello',
            'pains' => [null, null],
            'editorial' => [
                'do' => [null],
                'avoid' => [null],
            ],
            'references' => [
                ['quote' => null, 'structure' => null],
            ],
            'angles' => [
                [
                    'title' => null,
                    'hook' => null,
                    'format' => null,
                    'example' => null,
                ],
            ],
        ],
    ]);

    CampaignBriefAgent::fake(['Looks good.']);

    $this->actingAs($owner)
        ->postJson(route('api.company.campaigns.brief.chat', $campaign), [
            'message' => 'Check the brief.',
            'brief' => $campaign->brief,
        ])
        ->assertOk()
        ->assertJsonPath('data.brief.pains.0', '')
        ->assertJsonPath('data.brief.editorial.do.0', '')
        ->assertJsonPath('data.brief.angles.0.title', '');
});
