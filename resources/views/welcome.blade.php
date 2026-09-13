<x-layouts.marketing title="The B2B LinkedIn creator marketplace">
    <section class="px-6 py-24 sm:py-32" data-hero>
        <div class="mx-auto grid max-w-6xl items-center gap-16 lg:grid-cols-2">
            <div>
                <x-ui.kicker>B2B LinkedIn</x-ui.kicker>
                <h1 class="mt-6 max-w-xl text-5xl font-normal leading-[0.95] tracking-tight sm:text-6xl lg:text-7xl">
                    The B2B LinkedIn
                    <x-ui.em>creator marketplace.</x-ui.em>
                </h1>
                <p class="mt-8 max-w-[36rem] text-xl leading-relaxed text-foreground">
                    Find the creators your buyers already trust, launch campaigns in days, and track the clicks, leads, and pipeline from every post.
                </p>
                <div class="mt-10 flex flex-wrap gap-3">
                    <x-ui.button href="{{ route('register.company') }}" size="lg">Book creators</x-ui.button>
                    <x-ui.button href="{{ route('register.creator') }}" variant="secondary" size="lg">Get booked</x-ui.button>
                </div>
            </div>

            <x-ui.card flush>
                <div class="grid gap-1 px-6 py-5">
                    <x-ui.kicker>Campaign</x-ui.kicker>
                    <p class="text-lg">Find creators your buyers trust</p>
                </div>
                <div class="px-6 pb-5">
                    <p class="rounded-lg bg-muted px-4 py-3 text-sm leading-relaxed text-foreground">
                        Operators who already sell to VP Sales in EU SaaS. One post each, live this month.
                    </p>
                </div>
                <ul class="grid gap-1 px-6">
                    <li class="flex items-center justify-between py-2.5 text-sm">
                        <span>Eric</span>
                        <span class="text-primary">Fit 92%</span>
                    </li>
                    <li class="flex items-center justify-between py-2.5 text-sm">
                        <span>Robin</span>
                        <span class="text-primary">Fit 88%</span>
                    </li>
                    <li class="flex items-center justify-between py-2.5 text-sm">
                        <span>Aya</span>
                        <span class="text-primary">Fit 84%</span>
                    </li>
                </ul>
                <div class="mt-2 grid grid-cols-2 gap-6 px-6 py-5">
                    <div>
                        <p class="text-xs text-muted-foreground">Attributed pipeline</p>
                        <p class="mt-1 text-2xl">€48.2K</p>
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground">Leads</p>
                        <p class="mt-1 text-2xl">418</p>
                    </div>
                </div>
                <div class="px-6 pb-6">
                    <x-ui.button href="{{ route('register.company') }}">Book this shortlist</x-ui.button>
                </div>
            </x-ui.card>
        </div>
    </section>

    <section class="px-6 py-12" data-reveal>
        <div class="mx-auto max-w-6xl">
            <x-ui.kicker>Trusted by modern B2B teams</x-ui.kicker>
            <p class="mt-6 flex flex-wrap gap-x-10 gap-y-3 text-sm text-muted-foreground">
                <span>Demand gen</span>
                <span>Product marketing</span>
                <span>Agencies</span>
                <span>Scale-ups</span>
                <span>Operators</span>
            </p>
        </div>
    </section>

    <section class="px-6 py-24" data-reveal>
        <div class="mx-auto max-w-6xl">
            <x-ui.kicker>Case study</x-ui.kicker>
            <blockquote class="mt-8 max-w-3xl text-3xl font-normal leading-snug tracking-tight sm:text-4xl lg:text-5xl">
                “We manage €10M+ of influence budget every year. For B2B, Naano simply makes our life easier.”
            </blockquote>
            <p class="mt-8 text-sm">David Zmirov</p>
            <p class="text-sm text-muted-foreground">CEO, Zmirov Communication</p>
        </div>
    </section>

    <section class="px-6 py-24" data-reveal>
        <div class="mx-auto max-w-6xl">
            <h2 class="max-w-3xl text-4xl font-normal tracking-tight sm:text-5xl">
                Work with all the
                <x-ui.em>best creators.</x-ui.em>
            </h2>
            <p class="mt-6 max-w-xl text-lg leading-relaxed text-muted-foreground">
                Find the right B2B voices, compare audience fit, and book every collaboration from one place.
            </p>
            <div class="mt-16 grid gap-10 sm:grid-cols-3">
                <div>
                    <p class="text-4xl tracking-tight sm:text-5xl">3,000+</p>
                    <p class="mt-3 text-sm text-muted-foreground">Vetted creators. Specialist B2B voices, ready to collaborate.</p>
                </div>
                <div>
                    <p class="text-4xl tracking-tight sm:text-5xl">100</p>
                    <p class="mt-3 text-sm text-muted-foreground">Countries. Local expertise with genuinely global reach.</p>
                </div>
                <div>
                    <p class="text-4xl tracking-tight sm:text-5xl">Buyers first</p>
                    <p class="mt-3 text-sm text-muted-foreground">Matched to your buyers. Audience fit comes before follower count.</p>
                </div>
            </div>
        </div>
    </section>

    <section id="how-it-works" class="px-6 py-24" data-reveal>
        <div class="mx-auto max-w-6xl">
            <h2 class="max-w-3xl text-4xl font-normal tracking-tight sm:text-5xl">
                Run creator campaigns
                <x-ui.em>from one place.</x-ui.em>
            </h2>
            <p class="mt-6 max-w-xl text-lg leading-relaxed text-muted-foreground">
                Find the right voices, launch faster, and connect every post to measurable business results.
            </p>
            <ol class="mt-16 grid gap-12">
                <li class="grid gap-4 sm:grid-cols-[4.5rem_1fr]">
                    <p class="text-sm tracking-[0.18em] text-muted-foreground">01</p>
                    <div>
                        <p class="text-xl">Find creators your buyers trust</p>
                        <p class="mt-2 max-w-xl text-sm leading-relaxed text-muted-foreground">Compare audience fit, then shortlist the voices already in your buyers’ feed.</p>
                    </div>
                </li>
                <li class="grid gap-4 sm:grid-cols-[4.5rem_1fr]">
                    <p class="text-sm tracking-[0.18em] text-muted-foreground">02</p>
                    <div>
                        <p class="text-xl">Build a campaign brief in minutes</p>
                        <p class="mt-2 max-w-xl text-sm leading-relaxed text-muted-foreground">Objectives, key messages, and creator guidelines in one brief the whole campaign uses.</p>
                    </div>
                </li>
                <li class="grid gap-4 sm:grid-cols-[4.5rem_1fr]">
                    <p class="text-sm tracking-[0.18em] text-muted-foreground">03</p>
                    <div>
                        <p class="text-xl">Manage every collaboration</p>
                        <p class="mt-2 max-w-xl text-sm leading-relaxed text-muted-foreground">Drafts, schedules, and live posts stay on one thread until the work is done.</p>
                    </div>
                </li>
                <li class="grid gap-4 sm:grid-cols-[4.5rem_1fr]">
                    <p class="text-sm tracking-[0.18em] text-muted-foreground">04</p>
                    <div>
                        <p class="text-xl">Track reach, clicks, and leads</p>
                        <p class="mt-2 max-w-xl text-sm leading-relaxed text-muted-foreground">Attributed pipeline sits next to the post, so you know what each creator brought in.</p>
                    </div>
                </li>
            </ol>
        </div>
    </section>

    <section class="bg-primary text-primary-foreground" data-reveal>
        <div class="mx-auto max-w-6xl px-6 py-32 sm:py-40">
            <div class="mb-16 grid max-w-3xl gap-5">
                <x-ui.kicker tone="on-primary">The marketplace</x-ui.kicker>
                <h2 class="text-4xl font-normal tracking-tight sm:text-5xl">
                    One marketplace.
                    <x-ui.em>Two sides.</x-ui.em>
                </h2>
                <p class="max-w-xl text-lg leading-relaxed text-primary-foreground/75">
                    Companies book at a fixed price per post. Creators set a rate and get booked.
                </p>
            </div>

            <div id="companies" class="scroll-mt-24 grid items-center gap-10 border border-primary-foreground/15 p-8 sm:p-12 lg:grid-cols-2">
                <div class="grid gap-5">
                    <x-ui.kicker tone="on-primary">Companies</x-ui.kicker>
                    <p class="text-3xl font-normal tracking-tight sm:text-4xl">Book creators</p>
                    <details class="group border-t border-primary-foreground/15" open>
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-3 py-3.5 text-sm font-medium marker:content-none [&::-webkit-details-marker]:hidden">
                            Accept a short brief from your website
                            <span class="text-primary-foreground/50 group-open:hidden">+</span>
                            <span class="hidden text-primary-foreground/50 group-open:inline">−</span>
                        </summary>
                        <p class="max-w-md pb-3.5 text-sm leading-relaxed text-primary-foreground/75">
                            A short brief from your website. Objectives and key messages stay on one campaign.
                        </p>
                    </details>
                    <details class="group border-t border-primary-foreground/15">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-3 py-3.5 text-sm font-medium marker:content-none [&::-webkit-details-marker]:hidden">
                            Creators matched to your ICP
                            <span class="text-primary-foreground/50 group-open:hidden">+</span>
                            <span class="hidden text-primary-foreground/50 group-open:inline">−</span>
                        </summary>
                        <p class="max-w-md pb-3.5 text-sm leading-relaxed text-primary-foreground/75">
                            Compare audience fit, then shortlist the voices already in your buyers’ feed.
                        </p>
                    </details>
                    <details class="group border-t border-b border-primary-foreground/15">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-3 py-3.5 text-sm font-medium marker:content-none [&::-webkit-details-marker]:hidden">
                            Clicks, leads, and pipeline
                            <span class="text-primary-foreground/50 group-open:hidden">+</span>
                            <span class="hidden text-primary-foreground/50 group-open:inline">−</span>
                        </summary>
                        <p class="max-w-md pb-3.5 text-sm leading-relaxed text-primary-foreground/75">
                            Clicks, leads, and pipeline on the same campaign, next to the post that earned them.
                        </p>
                    </details>
                    <div class="pt-2">
                        <x-ui.button href="{{ route('register.company') }}" variant="inverted">Create a company account</x-ui.button>
                    </div>
                </div>
                <x-ui.card>
                    <x-ui.kicker>Campaign</x-ui.kicker>
                    <p class="mt-2 text-lg text-card-foreground">Find creators your buyers trust</p>
                    <ul class="mt-6 grid gap-1">
                        <li class="flex items-center justify-between py-2 text-sm">
                            <span>Eric</span>
                            <span class="text-primary">Fit 92%</span>
                        </li>
                        <li class="flex items-center justify-between py-2 text-sm">
                            <span>Robin</span>
                            <span class="text-primary">Fit 88%</span>
                        </li>
                        <li class="flex items-center justify-between py-2 text-sm">
                            <span>Aya</span>
                            <span class="text-primary">Fit 84%</span>
                        </li>
                    </ul>
                    <div class="mt-4 grid grid-cols-2 gap-6">
                        <div>
                            <p class="text-xs text-muted-foreground">Attributed pipeline</p>
                            <p class="mt-1 text-2xl">€48.2K</p>
                        </div>
                        <div>
                            <p class="text-xs text-muted-foreground">Leads</p>
                            <p class="mt-1 text-2xl">418</p>
                        </div>
                    </div>
                </x-ui.card>
            </div>

            <div id="creators" class="mt-8 scroll-mt-24 grid items-center gap-10 border border-primary-foreground/15 p-8 sm:p-12 lg:grid-cols-2">
                <div class="grid gap-5">
                    <x-ui.kicker tone="on-primary">Creators</x-ui.kicker>
                    <p class="text-3xl font-normal tracking-tight sm:text-4xl">Get booked</p>
                    <details class="group border-t border-primary-foreground/15" open>
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-3 py-3.5 text-sm font-medium marker:content-none [&::-webkit-details-marker]:hidden">
                            A marketplace card with your rate
                            <span class="text-primary-foreground/50 group-open:hidden">+</span>
                            <span class="hidden text-primary-foreground/50 group-open:inline">−</span>
                        </summary>
                        <p class="max-w-md pb-3.5 text-sm leading-relaxed text-primary-foreground/75">
                            A marketplace card with your public LinkedIn and net price.
                        </p>
                    </details>
                    <details class="group border-t border-primary-foreground/15">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-3 py-3.5 text-sm font-medium marker:content-none [&::-webkit-details-marker]:hidden">
                            Campaigns from B2B brands
                            <span class="text-primary-foreground/50 group-open:hidden">+</span>
                            <span class="hidden text-primary-foreground/50 group-open:inline">−</span>
                        </summary>
                        <p class="max-w-md pb-3.5 text-sm leading-relaxed text-primary-foreground/75">
                            Campaigns from B2B brands your buyers already know.
                        </p>
                    </details>
                    <details class="group border-t border-b border-primary-foreground/15">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-3 py-3.5 text-sm font-medium marker:content-none [&::-webkit-details-marker]:hidden">
                            Contract, invoice, and payout
                            <span class="text-primary-foreground/50 group-open:hidden">+</span>
                            <span class="hidden text-primary-foreground/50 group-open:inline">−</span>
                        </summary>
                        <p class="max-w-md pb-3.5 text-sm leading-relaxed text-primary-foreground/75">
                            Contract, invoice, and payout handled in the workspace.
                        </p>
                    </details>
                    <div class="pt-2">
                        <x-ui.button href="{{ route('register.creator') }}" variant="inverted">Join as a creator</x-ui.button>
                    </div>
                </div>
                <x-ui.card>
                    <x-ui.kicker>Creator</x-ui.kicker>
                    <p class="mt-2 text-lg text-card-foreground">Aya</p>
                    <p class="mt-1 text-sm text-muted-foreground">LinkedIn · EU SaaS operators</p>
                    <div class="mt-6">
                        <p class="text-xs text-muted-foreground">Net price</p>
                        <p class="mt-1 text-2xl">€1,200 / post</p>
                    </div>
                </x-ui.card>
            </div>
        </div>
    </section>

    <section class="px-6 py-24" data-reveal>
        <div class="mx-auto max-w-6xl">
            <h2 class="text-4xl font-normal tracking-tight sm:text-5xl">
                Proven across thousands of
                <x-ui.em>campaigns.</x-ui.em>
            </h2>
            <div class="mt-16 grid grid-cols-2 gap-10 lg:grid-cols-4">
                <div>
                    <p class="text-4xl tracking-tight sm:text-5xl">5M+</p>
                    <p class="mt-3 text-sm text-muted-foreground">Impressions generated</p>
                </div>
                <div>
                    <p class="text-4xl tracking-tight sm:text-5xl">30K+</p>
                    <p class="mt-3 text-sm text-muted-foreground">Leads generated</p>
                </div>
                <div>
                    <p class="text-4xl tracking-tight sm:text-5xl">2,000+</p>
                    <p class="mt-3 text-sm text-muted-foreground">Creators on Naano</p>
                </div>
                <div>
                    <p class="text-4xl tracking-tight sm:text-5xl">5K+</p>
                    <p class="mt-3 text-sm text-muted-foreground">Posts published</p>
                </div>
            </div>
        </div>
    </section>

    <section id="pricing" class="px-6 py-24" data-reveal>
        <div class="mx-auto max-w-6xl">
            <h2 class="text-4xl font-normal tracking-tight sm:text-5xl">
                Start free.
                <x-ui.em>Upgrade when you want time back.</x-ui.em>
            </h2>
            <p class="mt-6 max-w-xl text-lg text-muted-foreground">
                Run creator campaigns in-house, or have Naano operate the channel end to end.
            </p>
            <div class="mt-16 grid gap-6 lg:grid-cols-2">
                <x-ui.card>
                    <x-ui.kicker>Run it yourself</x-ui.kicker>
                    <p class="mt-6 text-5xl tracking-tight">Free</p>
                    <h3 class="mt-4 text-2xl font-normal">For teams that want the infrastructure.</h3>
                    <ul class="mt-8 grid gap-3 text-sm">
                        <li>Discover and book vetted LinkedIn creators</li>
                        <li>Brief, collaborate, and approve in one thread</li>
                        <li>Track clicks, leads, and pipeline per post</li>
                    </ul>
                    <div class="mt-10">
                        <x-ui.button href="{{ route('register.company') }}">Start free</x-ui.button>
                    </div>
                </x-ui.card>
                <x-ui.card>
                    <x-ui.kicker>Get your time back</x-ui.kicker>
                    <p class="mt-6 text-5xl tracking-tight">Operated</p>
                    <h3 class="mt-4 text-2xl font-normal">For teams that want Naano to operate the channel.</h3>
                    <ul class="mt-8 grid gap-3 text-sm">
                        <li>Campaign strategy and positioning</li>
                        <li>Creator sourcing, briefing, and launch</li>
                        <li>Reporting and optimisation</li>
                    </ul>
                    <div class="mt-10">
                        <x-ui.button href="{{ route('register.company') }}" variant="secondary">Talk to us</x-ui.button>
                    </div>
                </x-ui.card>
            </div>
        </div>
    </section>

    <section class="bg-foreground text-background" data-reveal>
        <div class="mx-auto max-w-6xl px-6 py-24">
            <x-ui.kicker tone="on-inverse">FAQ</x-ui.kicker>
            <h2 class="mt-6 text-4xl font-normal tracking-tight sm:text-5xl">
                Frequently asked
                <x-ui.em>questions.</x-ui.em>
            </h2>
            <p class="mt-6 text-background/65">Everything you need to know before getting started.</p>
            <div class="mt-16">
                <x-ui.accordion-item name="faq" tone="on-inverse" :open="true" question="What is Naano?">
                    Naano is a B2B LinkedIn creator marketplace. Companies discover and book vetted creators for sponsored LinkedIn campaigns, each at a fixed price per post set by the creator.
                </x-ui.accordion-item>
                <x-ui.accordion-item name="faq" tone="on-inverse" question="Who is this for?">
                    B2B teams that want posts from operators their buyers already follow, and creators who already publish on LinkedIn.
                </x-ui.accordion-item>
                <x-ui.accordion-item name="faq" tone="on-inverse" question="How does pricing work?">
                    The creator sets a net price per post. Companies book that rate. Start on the free workspace, then upgrade if you want Naano to run the channel.
                </x-ui.accordion-item>
                <x-ui.accordion-item name="faq" tone="on-inverse" question="What’s the difference between Run it yourself and Get your time back?">
                    Run it yourself is the marketplace: you source, brief, and book. Get your time back is operated by Naano: strategy, sourcing, launch, and reporting.
                </x-ui.accordion-item>
                <x-ui.accordion-item name="faq" tone="on-inverse" question="What happens after signup?">
                    Verify your email, finish a short setup, then open your company or creator workspace.
                </x-ui.accordion-item>
            </div>
        </div>
    </section>

    <section class="px-6 py-24" data-reveal>
        <div class="mx-auto max-w-6xl">
            <h2 class="max-w-3xl text-4xl font-normal tracking-tight sm:text-5xl lg:text-6xl">
                Your next creator campaign
                <x-ui.em>starts here.</x-ui.em>
            </h2>
            <p class="mt-6 max-w-xl text-lg text-muted-foreground">
                Get a creator strategy, a campaign format, and a workspace that can actually book the post.
            </p>
            <div class="mt-10 flex flex-wrap gap-3">
                <x-ui.button href="{{ route('register.company') }}" size="lg">Get started</x-ui.button>
                <x-ui.button href="{{ route('register.creator') }}" variant="secondary" size="lg">Join as a creator</x-ui.button>
            </div>
        </div>
    </section>
</x-layouts.marketing>
