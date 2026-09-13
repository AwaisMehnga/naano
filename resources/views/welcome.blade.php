<x-layouts.marketing title="The B2B LinkedIn creator marketplace">
    <section class="px-6 py-20 sm:py-28" data-hero>
        <div class="mx-auto grid max-w-6xl items-center gap-16 lg:grid-cols-2">
            <div>
                <x-ui.kicker>B2B LinkedIn</x-ui.kicker>
                <h1 class="mt-6 max-w-xl text-5xl font-normal leading-[1.05] tracking-tight sm:text-6xl">
                    The B2B LinkedIn
                    <x-ui.em>creator marketplace.</x-ui.em>
                </h1>
                <p class="mt-8 max-w-lg text-xl leading-relaxed text-foreground">
                    Find the creators your buyers already trust, launch campaigns in days, and track the clicks, leads, and pipeline from every post.
                </p>
                <div class="mt-10 flex flex-wrap gap-3">
                    <x-ui.button href="{{ route('register.company') }}" size="lg">Book creators</x-ui.button>
                    <x-ui.button href="{{ route('register.creator') }}" variant="secondary" size="lg">Get booked</x-ui.button>
                </div>
            </div>

            <x-ui.card flush>
                <div class="px-6 py-4">
                    <x-ui.kicker>Campaign</x-ui.kicker>
                    <p class="mt-2 text-lg">Find creators your buyers trust</p>
                </div>
                <ul class="grid gap-1 px-6 pb-4">
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
                <div class="grid grid-cols-2 gap-6 px-6 pb-5">
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
    </section>

    <section class="px-6 py-12" data-reveal>
        <div class="mx-auto max-w-6xl">
            <x-ui.kicker>Trusted by modern B2B teams</x-ui.kicker>
            <p class="mt-4 text-sm text-muted-foreground">
                Specialist voices, booked from one workspace, with every click on the same ledger.
            </p>
        </div>
    </section>

    <section class="px-6 py-24" data-reveal>
        <div class="mx-auto max-w-6xl">
            <x-ui.kicker>Case study</x-ui.kicker>
            <blockquote class="mt-8 max-w-3xl text-3xl font-normal leading-snug tracking-tight sm:text-4xl">
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
                    <p class="text-3xl">3,000+</p>
                    <p class="mt-2 text-sm text-muted-foreground">Vetted creators. Specialist B2B voices, ready to collaborate.</p>
                </div>
                <div>
                    <p class="text-3xl">100</p>
                    <p class="mt-2 text-sm text-muted-foreground">Countries. Local expertise with genuinely global reach.</p>
                </div>
                <div>
                    <p class="text-3xl">Buyers first</p>
                    <p class="mt-2 text-sm text-muted-foreground">Matched to your buyers. Audience fit comes before follower count.</p>
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
            <ol class="mt-16 grid gap-10">
                <li class="grid gap-4 sm:grid-cols-[4rem_1fr]">
                    <p class="text-sm text-muted-foreground">01</p>
                    <div>
                        <p class="text-lg">Find creators your buyers trust</p>
                        <p class="mt-2 max-w-xl text-sm text-muted-foreground">Compare audience fit, then shortlist the voices already in your buyers’ feed.</p>
                    </div>
                </li>
                <li class="grid gap-4 sm:grid-cols-[4rem_1fr]">
                    <p class="text-sm text-muted-foreground">02</p>
                    <div>
                        <p class="text-lg">Build a campaign brief in minutes</p>
                        <p class="mt-2 max-w-xl text-sm text-muted-foreground">Objectives, key messages, and creator guidelines in one brief the whole campaign uses.</p>
                    </div>
                </li>
                <li class="grid gap-4 sm:grid-cols-[4rem_1fr]">
                    <p class="text-sm text-muted-foreground">03</p>
                    <div>
                        <p class="text-lg">Manage every collaboration</p>
                        <p class="mt-2 max-w-xl text-sm text-muted-foreground">Drafts, schedules, and live posts stay on one thread until the work is done.</p>
                    </div>
                </li>
                <li class="grid gap-4 sm:grid-cols-[4rem_1fr]">
                    <p class="text-sm text-muted-foreground">04</p>
                    <div>
                        <p class="text-lg">Track reach, clicks, and leads</p>
                        <p class="mt-2 max-w-xl text-sm text-muted-foreground">Attributed pipeline sits next to the post, so you know what each creator brought in.</p>
                    </div>
                </li>
            </ol>
        </div>
    </section>

    <section data-reveal>
        <div class="mx-auto grid max-w-6xl gap-16 lg:grid-cols-2">
            <div id="companies" class="px-6 py-24">
                <x-ui.kicker>Companies</x-ui.kicker>
                <h2 class="mt-6 text-3xl font-normal tracking-tight sm:text-4xl">
                    Book creators at a
                    <x-ui.em>fixed price</x-ui.em>
                    per post.
                </h2>
                <ul class="mt-8 grid gap-3 text-sm">
                    <li>A short brief from your website</li>
                    <li>Creators matched to your ICP</li>
                    <li>Clicks, leads, and pipeline on the same campaign</li>
                </ul>
                <div class="mt-10">
                    <x-ui.button href="{{ route('register.company') }}">Create a company account</x-ui.button>
                </div>
            </div>
            <div id="creators" class="px-6 py-24">
                <x-ui.kicker>Creators</x-ui.kicker>
                <h2 class="mt-6 text-3xl font-normal tracking-tight sm:text-4xl">
                    Set a rate.
                    <x-ui.em>Get booked.</x-ui.em>
                </h2>
                <ul class="mt-8 grid gap-3 text-sm">
                    <li>A marketplace card with your public LinkedIn and net price</li>
                    <li>Campaigns from B2B brands your buyers already know</li>
                    <li>Contract, invoice, and payout handled in the workspace</li>
                </ul>
                <div class="mt-10">
                    <x-ui.button href="{{ route('register.creator') }}">Join as a creator</x-ui.button>
                </div>
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
                    <p class="text-3xl">5M+</p>
                    <p class="mt-2 text-sm text-muted-foreground">Impressions generated</p>
                </div>
                <div>
                    <p class="text-3xl">30K+</p>
                    <p class="mt-2 text-sm text-muted-foreground">Leads generated</p>
                </div>
                <div>
                    <p class="text-3xl">2,000+</p>
                    <p class="mt-2 text-sm text-muted-foreground">Creators on Naano</p>
                </div>
                <div>
                    <p class="text-3xl">5K+</p>
                    <p class="mt-2 text-sm text-muted-foreground">Posts published</p>
                </div>
            </div>
        </div>
    </section>

    <section id="pricing" data-reveal>
        <div class="mx-auto max-w-6xl px-6 py-24">
            <h2 class="text-4xl font-normal tracking-tight sm:text-5xl">
                Start free.
                <x-ui.em>Upgrade when you want time back.</x-ui.em>
            </h2>
            <p class="mt-6 max-w-xl text-lg text-muted-foreground">
                Run creator campaigns in-house, or have Naano operate the channel end to end.
            </p>
        </div>
        <div class="mx-auto grid max-w-6xl gap-16 lg:grid-cols-2">
            <div class="px-6 pb-16">
                <x-ui.kicker>Run it yourself</x-ui.kicker>
                <h3 class="mt-4 text-2xl font-normal">For teams that want the infrastructure.</h3>
                <ul class="mt-8 grid gap-3 text-sm">
                    <li>Discover and book vetted LinkedIn creators</li>
                    <li>Brief, collaborate, and approve in one thread</li>
                    <li>Track clicks, leads, and pipeline per post</li>
                </ul>
                <div class="mt-10">
                    <x-ui.button href="{{ route('register.company') }}">Start free</x-ui.button>
                </div>
            </div>
            <div class="px-6 pb-16">
                <x-ui.kicker>Get your time back</x-ui.kicker>
                <h3 class="mt-4 text-2xl font-normal">For teams that want Naano to operate the channel.</h3>
                <ul class="mt-8 grid gap-3 text-sm">
                    <li>Campaign strategy and positioning</li>
                    <li>Creator sourcing, briefing, and launch</li>
                    <li>Reporting and optimisation</li>
                </ul>
                <div class="mt-10">
                    <x-ui.button href="{{ route('register.company') }}" variant="secondary">Talk to us</x-ui.button>
                </div>
            </div>
        </div>
    </section>

    <section class="px-6 py-24" data-reveal>
        <div class="mx-auto max-w-6xl">
            <h2 class="text-4xl font-normal tracking-tight sm:text-5xl">
                Frequently asked
                <x-ui.em>questions.</x-ui.em>
            </h2>
            <p class="mt-6 text-muted-foreground">Everything you need to know before getting started.</p>
            <div class="mt-16 grid gap-10">
                <div class="grid gap-3">
                    <p class="text-lg">What is Naano?</p>
                    <p class="max-w-3xl text-sm leading-relaxed text-muted-foreground">
                        Naano is a B2B LinkedIn creator marketplace. Companies discover and book vetted creators for sponsored LinkedIn campaigns, each at a fixed price per post set by the creator.
                    </p>
                </div>
                <div class="grid gap-3">
                    <p class="text-lg">Who is this for?</p>
                    <p class="max-w-3xl text-sm leading-relaxed text-muted-foreground">
                        B2B teams that want posts from operators their buyers already follow, and creators who already publish on LinkedIn.
                    </p>
                </div>
                <div class="grid gap-3">
                    <p class="text-lg">How does pricing work?</p>
                    <p class="max-w-3xl text-sm leading-relaxed text-muted-foreground">
                        The creator sets a net price per post. Companies book that rate. Start on the free workspace, then upgrade if you want Naano to run the channel.
                    </p>
                </div>
                <div class="grid gap-3">
                    <p class="text-lg">What’s the difference between Run it yourself and Get your time back?</p>
                    <p class="max-w-3xl text-sm leading-relaxed text-muted-foreground">
                        Run it yourself is the marketplace: you source, brief, and book. Get your time back is operated by Naano: strategy, sourcing, launch, and reporting.
                    </p>
                </div>
    <div class="grid gap-3">
                    <p class="text-lg">What happens after signup?</p>
                    <p class="max-w-3xl text-sm leading-relaxed text-muted-foreground">
                        Verify your email, finish a short setup, then open your company or creator workspace.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <section class="px-6 py-24" data-reveal>
        <div class="mx-auto max-w-6xl">
            <h2 class="max-w-3xl text-4xl font-normal tracking-tight sm:text-5xl">
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
