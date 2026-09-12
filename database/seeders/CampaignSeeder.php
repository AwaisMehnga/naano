<?php

namespace Database\Seeders;

use App\Enums\CampaignObjective;
use App\Enums\CampaignStatus;
use App\Enums\CampaignType;
use App\Enums\CollaborationSource;
use App\Enums\CollaborationStatus;
use App\Enums\LeadSource;
use App\Enums\PostStatus;
use App\Models\Campaign;
use App\Models\Collaboration;
use App\Models\Company;
use App\Models\CreatorProfile;
use App\Models\Lead;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class CampaignSeeder extends Seeder
{
    /**
     * Seed named campaigns so the company workspace has something to open.
     */
    public function run(): void
    {
        foreach (Company::query()->orderBy('id')->get() as $company) {
            $ownerId = $company->user_id;

            if ($this->isDemoCompany($company)) {
                $this->seedDemoWorkspace($company, $ownerId);

                continue;
            }

            foreach (['Q4 Pipeline', 'Thought leadership EU', 'Product launch', 'Hiring brand'] as $name) {
                Campaign::query()->firstOrCreate(
                    [
                        'company_id' => $company->id,
                        'name' => $name,
                    ],
                    [
                        'type' => CampaignType::ThoughtLeadership,
                        'objective' => CampaignObjective::Pipeline,
                        'status' => CampaignStatus::Active,
                        'budget_cents' => 100000,
                        'created_by_user_id' => $ownerId,
                    ],
                );
            }
        }
    }

    private function isDemoCompany(Company $company): bool
    {
        return User::query()
            ->where('id', $company->user_id)
            ->where('email', 'test@example.com')
            ->exists();
    }

    private function seedDemoWorkspace(Company $company, int $ownerId): void
    {
        $creators = User::query()
            ->whereIn('email', [
                'somitra@example.com',
                'phil@example.com',
                'anastasia@example.com',
                'lea@example.com',
                'jonah@example.com',
                'nora@example.com',
                'mateo@example.com',
                'aisha@example.com',
            ])
            ->with('creatorProfile')
            ->get()
            ->mapWithKeys(fn (User $user) => [$user->email => $user->creatorProfile])
            ->filter();

        $visibility = $this->upsertCampaign($company, $ownerId, 'LinkedIn visibility push', [
            'type' => CampaignType::ThoughtLeadership,
            'objective' => CampaignObjective::Awareness,
            'status' => CampaignStatus::Active,
            'budget_cents' => 250000,
            'start_at' => now()->subDays(12),
            'end_at' => now()->addWeeks(2),
            'brief' => $this->awaisBrief(),
            'goal' => $this->awaisBrief()['context'],
            'key_messages' => [
                ...$this->awaisBrief()['differentiators'],
                $this->awaisBrief()['key_message'],
            ],
            'guidelines' => "Do: Show real code, repos or project specifics whenever referenced\nAvoid: Never invent client names, testimonials or performance numbers not in the source material",
        ]);

        $pipeline = $this->upsertCampaign($company, $ownerId, 'Q4 Pipeline', [
            'type' => CampaignType::ThoughtLeadership,
            'objective' => CampaignObjective::Pipeline,
            'status' => CampaignStatus::Active,
            'budget_cents' => 180000,
            'brief' => [
                'context' => 'A two-week LinkedIn push to put the product in front of operators who already buy creator-led pipeline.',
                'product' => 'Naano books vetted LinkedIn creators against a fixed per-post rate.',
                'differentiators' => ['Fit over follower count', 'Fixed creator prices'],
                'target' => 'B2B SaaS marketers in Europe',
                'pains' => ['Hard to tell which creators actually reach buyers'],
                'trigger' => 'A demand-gen lead needs pipeline this quarter.',
                'key_message' => 'Book creators who already speak to your buyers.',
                'audience' => [
                    'industries' => 'B2B, SaaS',
                    'geographies' => 'Europe',
                    'tone' => 'Direct and specific.',
                ],
                'editorial' => [
                    'do' => ['Name the buyer and the proof'],
                    'avoid' => ['Generic thought-leadership filler'],
                ],
                'references' => [],
                'angles' => [],
            ],
            'goal' => 'A two-week LinkedIn push to put the product in front of operators who already buy creator-led pipeline.',
            'key_messages' => ['Fit over follower count', 'Book creators who already speak to your buyers.'],
        ]);

        $this->upsertCampaign($company, $ownerId, 'Product launch', [
            'type' => CampaignType::Product,
            'objective' => CampaignObjective::Pipeline,
            'status' => CampaignStatus::Draft,
            'budget_cents' => 120000,
        ]);

        $hiring = $this->upsertCampaign($company, $ownerId, 'Hiring brand', [
            'type' => CampaignType::Hiring,
            'objective' => CampaignObjective::Talent,
            'status' => CampaignStatus::Completed,
            'budget_cents' => 90000,
            'start_at' => now()->subMonths(2),
            'end_at' => now()->subWeeks(2),
            'brief' => [
                'context' => 'A completed hiring campaign that used creator posts to attract full-stack collaborators.',
                'product' => 'A personal brand platform for a full-stack developer.',
                'differentiators' => ['Public code as proof'],
                'target' => 'Engineering leads in Europe',
                'pains' => ['Resumes hide how someone actually thinks'],
                'trigger' => 'A team is hiring a full-stack developer.',
                'key_message' => 'See the work before you take the call.',
                'audience' => [
                    'industries' => 'Software',
                    'geographies' => 'Europe',
                    'tone' => 'Peer to peer.',
                ],
                'editorial' => [
                    'do' => ['Show repos'],
                    'avoid' => ['Invent hires'],
                ],
                'references' => [],
                'angles' => [],
            ],
        ]);

        if ($creators->count() < 6) {
            return;
        }

        $this->seedVisibilityCollaborations($visibility, $ownerId, $creators);
        $this->seedInvites($pipeline, $ownerId, $creators);
        $this->seedCompletedHiring($hiring, $creators);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function upsertCampaign(Company $company, int $ownerId, string $name, array $attributes): Campaign
    {
        $campaign = Campaign::query()->firstOrCreate(
            [
                'company_id' => $company->id,
                'name' => $name,
            ],
            [
                ...$attributes,
                'created_by_user_id' => $ownerId,
            ],
        );

        if (! $campaign->wasRecentlyCreated) {
            $campaign->fill($attributes);
            $campaign->save();
        }

        return $campaign->refresh();
    }

    /**
     * @param  Collection<string, CreatorProfile>  $creators
     */
    private function seedVisibilityCollaborations(Campaign $campaign, int $ownerId, $creators): void
    {
        if ($campaign->collaborations()->exists()) {
            return;
        }

        $rows = [
            ['email' => 'somitra@example.com', 'status' => CollaborationStatus::Invited, 'source' => CollaborationSource::Invite],
            ['email' => 'phil@example.com', 'status' => CollaborationStatus::Applied, 'source' => CollaborationSource::Apply],
            ['email' => 'anastasia@example.com', 'status' => CollaborationStatus::Selected, 'source' => CollaborationSource::Invite],
            ['email' => 'lea@example.com', 'status' => CollaborationStatus::Selected, 'source' => CollaborationSource::Invite],
            ['email' => 'jonah@example.com', 'status' => CollaborationStatus::Outreach, 'source' => CollaborationSource::Sourced],
            ['email' => 'nora@example.com', 'status' => CollaborationStatus::Booked, 'source' => CollaborationSource::Invite],
            ['email' => 'mateo@example.com', 'status' => CollaborationStatus::Completed, 'source' => CollaborationSource::Invite],
        ];

        foreach ($rows as $row) {
            $profile = $creators->get($row['email']);

            if ($profile === null) {
                continue;
            }

            $collaboration = Collaboration::query()->create([
                'campaign_id' => $campaign->id,
                'creator_profile_id' => $profile->id,
                'source' => $row['source'],
                'status' => $row['status'],
                'invited_by_user_id' => $row['source'] === CollaborationSource::Invite ? $ownerId : null,
                'accepted_at' => in_array($row['status'], [CollaborationStatus::Selected, CollaborationStatus::Booked, CollaborationStatus::Completed], true) ? now()->subDays(5) : null,
                'booked_at' => in_array($row['status'], [CollaborationStatus::Booked, CollaborationStatus::Completed], true) ? now()->subDays(4) : null,
                'booked_price_cents' => in_array($row['status'], [CollaborationStatus::Booked, CollaborationStatus::Completed], true) ? $profile->price_cents : null,
                'booked_posts_count' => in_array($row['status'], [CollaborationStatus::Booked, CollaborationStatus::Completed], true) ? 1 : null,
            ]);

            if ($row['status'] === CollaborationStatus::Booked) {
                Post::query()->create([
                    'collaboration_id' => $collaboration->id,
                    'status' => PostStatus::InReview,
                    'body' => 'Anyone can say they are a great developer. Not everyone can show the receipts. The work has to live where people can inspect it — repos, articles, case studies — before the first call.',
                    'submitted_at' => now()->subDay(),
                ]);
            }

            if ($row['status'] === CollaborationStatus::Completed) {
                Post::query()->create([
                    'collaboration_id' => $collaboration->id,
                    'status' => PostStatus::Published,
                    'body' => 'Visibility does not guarantee opportunities. But invisibility guarantees you will never get the call. Put the proof where people can find it.',
                    'published_url' => 'https://www.linkedin.com/posts/mateo-demo-1',
                    'published_at' => now()->subDays(3),
                    'submitted_at' => now()->subDays(6),
                ]);
            }

            if ($row['status'] === CollaborationStatus::Selected) {
                Post::query()->create([
                    'collaboration_id' => $collaboration->id,
                    'status' => PostStatus::Draft,
                    'body' => 'Draft: developers and decision-makers read portfolios completely differently.',
                ]);
            }
        }

        foreach (range(1, 4) as $offset) {
            Lead::query()->create([
                'company_id' => $campaign->company_id,
                'campaign_id' => $campaign->id,
                'occurred_at' => now()->subDays($offset),
                'source' => LeadSource::Manual,
                'payload' => ['note' => 'Seeded inbound from LinkedIn'],
            ]);
        }
    }

    /**
     * @param  Collection<string, CreatorProfile>  $creators
     */
    private function seedInvites(Campaign $campaign, int $ownerId, $creators): void
    {
        if ($campaign->collaborations()->exists()) {
            return;
        }

        foreach (['aisha@example.com', 'somitra@example.com'] as $email) {
            $profile = $creators->get($email);

            if ($profile === null) {
                continue;
            }

            Collaboration::query()->create([
                'campaign_id' => $campaign->id,
                'creator_profile_id' => $profile->id,
                'source' => CollaborationSource::Invite,
                'status' => CollaborationStatus::Invited,
                'invited_by_user_id' => $ownerId,
            ]);
        }
    }

    /**
     * @param  Collection<string, CreatorProfile>  $creators
     */
    private function seedCompletedHiring(Campaign $campaign, $creators): void
    {
        if ($campaign->collaborations()->exists()) {
            return;
        }

        $profile = $creators->get('phil@example.com');

        if ($profile === null) {
            return;
        }

        $collaboration = Collaboration::query()->create([
            'campaign_id' => $campaign->id,
            'creator_profile_id' => $profile->id,
            'source' => CollaborationSource::Invite,
            'status' => CollaborationStatus::Completed,
            'booked_price_cents' => $profile->price_cents,
            'booked_posts_count' => 1,
            'accepted_at' => now()->subMonth(),
            'booked_at' => now()->subMonth(),
        ]);

        Post::query()->create([
            'collaboration_id' => $collaboration->id,
            'status' => PostStatus::Published,
            'body' => 'Hiring managers need fast signals of reliability. Public work is that signal.',
            'published_url' => 'https://www.linkedin.com/posts/phil-demo-hiring',
            'published_at' => now()->subWeeks(3),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function awaisBrief(): array
    {
        return [
            'context' => "Muhammad Awais Mehnga is a personal brand and portfolio platform for a full-stack developer, showcasing open-source contributions, technical articles and project case studies.\nThis short, focused 1–2 week LinkedIn push aims to build visibility across the whole tech ecosystem — both developers who might use or contribute to the platform, and business decision-makers who might hire or partner with Awais.\nThe brand wants to lead with social proof, but no verified customer testimonials or quantified results currently exist in the source material — so posts should focus on demonstrable technical proof (real repos, articles, projects) rather than invented client outcomes.\nThe end goal highlighted by the brand is hiring or partnership opportunities arising from visibility, not raw lead volume.",
            'product' => "Muhammad Awais Mehnga's platform is a portfolio and thought-leadership hub showing real open-source and full-stack work, helping developers, decision-makers and potential collaborators quickly evaluate his technical skill and track record.",
            'differentiators' => [
                'Combines portfolio, open-source repos and technical articles in one hub',
                'Speaks credibly to both technical peers and business decision-makers',
                'Demonstrates full-stack and modern web technology depth through real project case studies',
            ],
            'target' => 'Developers & Tech Professionals / Business Decision-Makers (Ops, Product, Engineering Leads) · B2B, Developer Tools & Software · Startups and scale-ups · Europe',
            'pains' => [
                "Decision-makers struggle to verify a freelancer/developer's real skill beyond a resume",
                "Developers want to see credible peers' open-source work before engaging or referring",
                'Hiring managers need fast signals of reliability and technical depth before a call',
            ],
            'trigger' => 'A team or founder is actively looking for a reliable full-stack developer or technical collaborator and needs quick, credible proof of capability before reaching out.',
            'key_message' => 'See the work before you take the call.',
            'audience' => [
                'industries' => 'B2B, Developer Tools, Software',
                'geographies' => 'Europe',
                'tone' => 'Direct, confident, peer-to-peer — like a skilled developer talking shop with other builders, not a marketer selling a service. Uses plain technical vocabulary, short sentences, no corporate buzzwords, no forced enthusiasm.',
            ],
            'editorial' => [
                'do' => [
                    'Show real code, repos or project specifics whenever referenced',
                    'Speak directly to both developers and hiring/business audiences in the same post',
                    'Use concrete technical language (stack, architecture, performance) instead of vague claims',
                    'End with a low-pressure CTA (DM, comment, check the portfolio)',
                    'Publish during weekday mornings when tech audiences are most active',
                ],
                'avoid' => [
                    'Never invent client names, testimonials or performance numbers not in the source material',
                    "Avoid generic hype phrases like 'game-changer' or 'passionate developer'",
                    "Don't oversell partnerships or hires that haven't actually happened",
                    "Avoid heavy corporate jargon ('synergy', 'leverage', 'disruptive')",
                    "Don't post the same message twice — vary hook and structure per post",
                ],
            ],
            'references' => [
                [
                    'quote' => "Most developer portfolios show projects.\nAlmost none show how someone actually thinks.",
                    'structure' => "Open with the observation that portfolios rarely reveal reasoning → Point to Awais's platform where code, articles and case studies show the thought process behind decisions → Invite readers to explore the repos and articles themselves and share feedback.",
                ],
                [
                    'quote' => "Here's a free checklist for evaluating a developer's portfolio before you hire or partner with them.",
                    'structure' => "Introduce the problem of judging technical skill from the outside → Offer a short checklist (code quality signals, project scope, communication in docs) as a free resource inspired by reviewing Awais's own portfolio → CTA to comment 'checklist' or DM to receive it.",
                ],
            ],
            'angles' => [
                [
                    'title' => 'Technical proof over claims',
                    'hook' => "Anyone can say 'I'm a great developer.' Not everyone can show the receipts.",
                    'format' => 'Thought leadership. Shows how real repos, articles and case studies on the platform let anyone verify skill directly, appealing to skeptical developers and decision-makers alike.',
                    'example' => '',
                ],
                [
                    'title' => 'Free portfolio evaluation checklist',
                    'hook' => "Free checklist: how to actually judge a developer's portfolio (not just skim it).",
                    'format' => "Checklist. Offers a practical checklist for judging developer portfolios, indirectly demonstrating the quality standard Awais's own site is built to.",
                    'example' => "Free checklist: how to actually judge a developer's portfolio — not just skim it.\nMost people scroll a portfolio for 15 seconds, see some logos, and move on.\nThat's not evaluation, that's guessing.\nHere's what actually matters when reviewing a developer's work, especially before hiring or partnering:\n→ Is the code public and readable, or just described?\n→ Do the write-ups explain decisions, not just results?\n→ Are the projects varied enough to show range, not just repetition?\n→ Is there evidence of ongoing contribution (open-source, articles) vs. a one-time showcase?\nI put together a short checklist covering these points in more detail — built after reviewing what actually separates a strong technical portfolio (like the one at awaismehnga.dev) from a decorative one.\nWant it?\nComment 'checklist' or DM me and I'll send it over.\nWhat would you add to this list?",
                ],
                [
                    'title' => 'Bridging developers and business decision-makers',
                    'hook' => 'Developers and decision-makers read portfolios completely differently. Most sites only speak to one of them.',
                    'format' => 'Contre-pied. Challenges the assumption that technical work and business value are separate conversations, positioning the platform as speaking credibly to both audiences.',
                    'example' => "Developers and decision-makers read portfolios completely differently.\nA developer wants to see code quality, architecture choices, contribution history.\nA decision-maker wants to know: can this person solve my problem, communicate clearly, and be trusted with real responsibility.\nMost personal portfolio sites pick one audience and ignore the other.\nThat's a mistake — because the people who end up hiring or partnering with a developer are rarely just other developers.\nMuhammad Awais Mehnga's site tries to hold both conversations at once: open-source repos and technical articles for the peer audience, and clear case studies of real projects for the business audience deciding whether to reach out.\nIf you're building your own presence as a developer, ask yourself: does your portfolio actually speak to the person who might hire you, not just the person who might star your repo?\nHappy to compare notes if you're working on this too.",
                ],
                [
                    'title' => 'From visibility to opportunity',
                    'hook' => "Visibility doesn't guarantee opportunities. But invisibility guarantees you'll never get the call.",
                    'format' => "Retour d'XP.",
                    'example' => "Visibility doesn't guarantee opportunities.\nBut invisibility guarantees you'll never get the call.\nA lot of skilled developers stay quiet — no articles, no visible repos, no case studies — and then wonder why recruiters or potential collaborators never reach out first.\nThe idea behind building out a platform like this one is simple: put the proof where people can find it, before they even ask for it.\nOpen-source contributions people can actually inspect.\nArticles that show how you think, not just what you know.\nProject write-ups that read like real case studies, not marketing copy.\nThe hope isn't just traffic — it's the right conversation starting one step earlier: a hiring manager who already trusts your work before the first call, or a potential collaborator who reaches out because they've seen exactly how you operate.\nIf you're a developer thinking about your own visibility, what's one piece of proof you could publish this month?",
                ],
            ],
        ];
    }
}
