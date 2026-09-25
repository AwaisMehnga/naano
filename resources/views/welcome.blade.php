@php
    $creators = [
        [
            'name' => 'Eric',
            'role' => 'VP Sales · EU SaaS',
            'match' => '92%',
            'followers' => '12.4K',
            'engagement' => '410',
            'rate' => '€1,400',
            'image' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=800&h=1000&q=80',
        ],
        [
            'name' => 'Robin',
            'role' => 'Founder · Demand gen',
            'match' => '88%',
            'followers' => '28K',
            'engagement' => '520',
            'rate' => '€900',
            'image' => 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&w=800&h=1000&q=80',
        ],
        [
            'name' => 'Aya',
            'role' => 'Operator · LinkedIn',
            'match' => '84%',
            'followers' => '9.1K',
            'engagement' => '336',
            'rate' => '€1,200',
            'image' => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=800&h=1000&q=80',
        ],
        [
            'name' => 'Sam',
            'role' => 'GTM Lead · SaaS',
            'match' => '91%',
            'followers' => '18K',
            'engagement' => '480',
            'rate' => '€1,100',
            'image' => 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?auto=format&fit=crop&w=800&h=1000&q=80',
        ],
    ];

    $heroCreators = array_slice($creators, 0, 3);
@endphp

<x-layouts.marketing title="The B2B LinkedIn creator marketplace">
    {{-- Hero --}}
    <section class="relative overflow-hidden px-6 pb-14 pt-14 sm:pb-16 sm:pt-16 lg:px-12 lg:pt-20 xl:px-16" data-hero>
        <div class="grid w-full items-center gap-12 lg:grid-cols-2 lg:gap-10">
            <div class="max-w-2xl">
                <h1 class="text-5xl font-normal leading-[0.95] tracking-tight sm:text-6xl lg:text-7xl">
                    The B2B LinkedIn
                    <x-ui.em>creator marketplace.</x-ui.em>
                </h1>
                <p class="mt-6 max-w-md text-lg text-muted-foreground">
                    Book creators your buyers already trust. Pay when the post is live.
                </p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <x-ui.button href="{{ route('register.company') }}" size="lg">Book creators</x-ui.button>
                    <x-ui.button href="{{ route('register.creator') }}" variant="secondary" size="lg">Get booked</x-ui.button>
                </div>
            </div>

            <div class="relative mx-auto w-full max-w-lg lg:mx-0 lg:max-w-none" data-hero-visual>
                <div class="relative mx-auto flex h-[22rem] w-full items-end justify-center sm:h-[24rem] lg:h-[26rem]">
                    @foreach ($heroCreators as $index => $creator)
                        @php
                            $cardClass = match ($index) {
                                0 => 'absolute bottom-2 left-[14%] z-0 w-[11rem] -rotate-6 sm:left-[18%] sm:w-[12.5rem] lg:w-[13.5rem]',
                                1 => 'absolute bottom-0 left-1/2 z-20 w-[12rem] -translate-x-1/2 sm:w-[13.5rem] lg:w-[14.5rem]',
                                default => 'absolute bottom-2 right-[14%] z-10 w-[11rem] rotate-6 sm:right-[18%] sm:w-[12.5rem] lg:w-[13.5rem]',
                            };
                        @endphp
                        <article data-hero-card class="{{ $cardClass }} overflow-hidden rounded-3xl border border-border bg-card">
                            <div class="relative">
                                <span class="absolute left-3 top-3 z-10 rounded-pill bg-accent px-2.5 py-1 text-[11px] font-medium text-accent-foreground">
                                    {{ $creator['match'] }} match
                                </span>
                                <img
                                    src="{{ $creator['image'] }}"
                                    alt="{{ $creator['name'] }}"
                                    class="aspect-square w-full object-cover"
                                    loading="{{ $index === 1 ? 'eager' : 'lazy' }}"
                                    width="400"
                                    height="400"
                                >
                            </div>
                            <div class="grid gap-3 p-3.5">
                                <div>
                                    <p class="text-sm font-medium tracking-tight">{{ $creator['name'] }}</p>
                                    <p class="mt-0.5 line-clamp-1 text-xs text-muted-foreground">{{ $creator['role'] }}</p>
                                </div>
                                <div class="grid grid-cols-3 gap-1 border-t border-border pt-2.5 text-center">
                                    <div>
                                        <p class="text-xs font-medium">{{ $creator['followers'] }}</p>
                                        <p class="text-[10px] text-muted-foreground">Followers</p>
                                    </div>
                                    <div>
                                        <p class="text-xs font-medium">{{ $creator['engagement'] }}</p>
                                        <p class="text-[10px] text-muted-foreground">Engagement</p>
                                    </div>
                                    <div>
                                        <p class="text-xs font-medium">{{ $creator['rate'] }}</p>
                                        <p class="text-[10px] text-muted-foreground">Per post</p>
                                    </div>
                                </div>
                                <a href="{{ route('register.company') }}" class="inline-flex w-full items-center justify-center rounded-pill bg-accent py-2 text-xs font-medium text-accent-foreground">Shortlist</a>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="mt-14 flex flex-wrap gap-x-12 gap-y-6 border-t border-border pt-8" data-reveal>
            <div>
                <p class="text-3xl tracking-tight sm:text-4xl">3,000+</p>
                <p class="mt-1 text-sm text-muted-foreground">Creators</p>
            </div>
            <div>
                <p class="text-3xl tracking-tight sm:text-4xl">100</p>
                <p class="mt-1 text-sm text-muted-foreground">Countries</p>
            </div>
            <div>
                <p class="text-3xl tracking-tight sm:text-4xl">5M+</p>
                <p class="mt-1 text-sm text-muted-foreground">Impressions</p>
            </div>
            <div>
                <p class="text-3xl tracking-tight sm:text-4xl">30K+</p>
                <p class="mt-1 text-sm text-muted-foreground">Leads</p>
            </div>
        </div>
    </section>

    {{-- How it works --}}
    <section id="how-it-works" class="scroll-mt-24 bg-card px-6 py-20 lg:px-12 xl:px-16" data-reveal>
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <h2 class="max-w-xl text-4xl font-normal tracking-tight sm:text-5xl">
                Run campaigns
                <x-ui.em>from one place.</x-ui.em>
            </h2>
        </div>

        <div class="mt-12 grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
            <article class="overflow-hidden rounded-3xl border border-border bg-background">
                <div class="grid grid-cols-3 gap-2 p-4">
                    @foreach (array_slice($creators, 0, 3) as $creator)
                        <img
                            src="{{ $creator['image'] }}"
                            alt="{{ $creator['name'] }}"
                            class="aspect-square w-full rounded-2xl object-cover"
                            loading="lazy"
                            width="120"
                            height="120"
                        >
                    @endforeach
                </div>
                <div class="border-t border-border px-5 py-4">
                    <p class="text-xs tracking-[0.18em] text-muted-foreground">01</p>
                    <h3 class="mt-2 text-lg">Find creators</h3>
                </div>
            </article>

            <article class="overflow-hidden rounded-3xl border border-border bg-background">
                <div class="p-4">
                    <div class="rounded-2xl bg-muted p-4">
                        <div class="h-2.5 w-2/3 rounded-pill bg-card"></div>
                        <div class="mt-3 h-2.5 w-full rounded-pill bg-card"></div>
                        <div class="mt-2 h-2.5 w-5/6 rounded-pill bg-card"></div>
                        <div class="mt-6 flex gap-2">
                            <span class="rounded-pill bg-accent px-3 py-1 text-xs text-accent-foreground">Brief</span>
                            <span class="rounded-pill bg-card px-3 py-1 text-xs">Links</span>
                        </div>
                    </div>
                </div>
                <div class="border-t border-border px-5 py-4">
                    <p class="text-xs tracking-[0.18em] text-muted-foreground">02</p>
                    <h3 class="mt-2 text-lg">Write the brief</h3>
                </div>
            </article>

            <article class="overflow-hidden rounded-3xl border border-border bg-background">
                <div class="flex gap-2 p-4">
                    <img
                        src="{{ $creators[2]['image'] }}"
                        alt="{{ $creators[2]['name'] }}"
                        class="size-16 rounded-2xl object-cover"
                        loading="lazy"
                        width="64"
                        height="64"
                    >
                    <div class="flex-1 rounded-2xl bg-muted p-3">
                        <div class="h-2 w-3/4 rounded-pill bg-card"></div>
                        <div class="mt-2 h-2 w-full rounded-pill bg-card"></div>
                        <div class="mt-2 h-2 w-2/3 rounded-pill bg-card"></div>
                    </div>
                </div>
                <div class="border-t border-border px-5 py-4">
                    <p class="text-xs tracking-[0.18em] text-muted-foreground">03</p>
                    <h3 class="mt-2 text-lg">Approve drafts</h3>
                </div>
            </article>

            <article class="overflow-hidden rounded-3xl border border-border bg-background">
                <div class="p-4">
                    <div class="rounded-2xl bg-muted p-5">
                        <p class="text-3xl tracking-tight">€48.2K</p>
                        <p class="mt-1 text-xs text-muted-foreground">Pipeline</p>
                        <div class="mt-5 h-2 overflow-hidden rounded-pill bg-card">
                            <div class="h-full w-3/4 rounded-pill bg-primary"></div>
                        </div>
                    </div>
                </div>
                <div class="border-t border-border px-5 py-4">
                    <p class="text-xs tracking-[0.18em] text-muted-foreground">04</p>
                    <h3 class="mt-2 text-lg">Track results</h3>
                </div>
            </article>
        </div>
    </section>

    {{-- Shortlist --}}
    <section id="shortlist" class="scroll-mt-24 px-6 py-20 lg:px-12 xl:px-16" data-reveal>
        <div class="flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
            <h2 class="max-w-xl text-4xl font-normal tracking-tight sm:text-5xl">
                Build a shortlist
                <x-ui.em>in minutes.</x-ui.em>
            </h2>
            <x-ui.button href="{{ route('register.company') }}">Book creators</x-ui.button>
        </div>

        <div class="mt-12 grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($creators as $creator)
                <a href="{{ route('register.company') }}" class="overflow-hidden rounded-3xl border border-border bg-card transition-opacity hover:opacity-90">
                    <div class="relative">
                        <span class="absolute left-3 top-3 z-10 rounded-pill bg-accent px-2.5 py-1 text-[11px] font-medium text-accent-foreground">
                            {{ $creator['match'] }} match
                        </span>
                        <img
                            src="{{ $creator['image'] }}"
                            alt="{{ $creator['name'] }}"
                            class="aspect-[4/5] w-full object-cover"
                            loading="lazy"
                            width="400"
                            height="500"
                        >
                    </div>
                    <div class="grid gap-3 p-4">
                        <div>
                            <p class="font-medium tracking-tight">{{ $creator['name'] }}</p>
                            <p class="mt-0.5 text-sm text-muted-foreground">{{ $creator['role'] }}</p>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-muted-foreground">{{ $creator['followers'] }} followers</span>
                            <span class="font-medium">{{ $creator['rate'] }}</span>
                        </div>
                        <span class="inline-flex w-full items-center justify-center rounded-pill bg-accent py-2 text-xs font-medium text-accent-foreground">Shortlist</span>
                    </div>
                </a>
            @endforeach
        </div>
    </section>

    {{-- Quote --}}
    <section class="bg-card px-6 py-20 lg:px-12 xl:px-16" data-reveal>
        <div class="grid w-full items-center gap-10 lg:grid-cols-[14rem_1fr]">
            <img
                src="https://images.unsplash.com/photo-1560250097-0b93528c311a?auto=format&fit=crop&w=400&h=400&q=80"
                alt="David Zmirov"
                class="size-40 rounded-3xl object-cover lg:size-52"
                loading="lazy"
                width="208"
                height="208"
            >
            <div>
                <blockquote class="max-w-3xl text-3xl font-normal leading-snug tracking-tight sm:text-4xl">
                    “For B2B, Naano simply makes our life easier.”
                </blockquote>
                <p class="mt-6 text-sm">David Zmirov · CEO, Zmirov Communication</p>
            </div>
        </div>
    </section>

    {{-- Two sides --}}
    <section class="bg-primary text-primary-foreground" data-reveal>
        <div class="w-full px-6 py-20 lg:px-12 xl:px-16">
            <h2 class="max-w-2xl text-4xl font-normal tracking-tight sm:text-5xl">
                One marketplace.
                <x-ui.em>Two sides.</x-ui.em>
            </h2>

            <div class="mt-12 grid gap-5 lg:grid-cols-2">
                <div id="companies" class="scroll-mt-24 overflow-hidden rounded-3xl border border-primary-foreground/15 bg-primary">
                    <div class="grid sm:grid-cols-2">
                        <div class="flex flex-col justify-between gap-8 p-8 sm:p-10">
                            <div>
                                <x-ui.kicker tone="on-primary">Companies</x-ui.kicker>
                                <p class="mt-4 text-3xl tracking-tight">Book creators</p>
                            </div>
                            <x-ui.button href="{{ route('register.company') }}" variant="inverted">Create a company account</x-ui.button>
                        </div>
                        <div class="relative min-h-56">
                            <img
                                src="{{ $creators[1]['image'] }}"
                                alt=""
                                class="absolute inset-0 size-full object-cover"
                                loading="lazy"
                            >
                        </div>
                    </div>
                </div>

                <div id="creators" class="scroll-mt-24 overflow-hidden rounded-3xl border border-primary-foreground/15 bg-primary">
                    <div class="grid sm:grid-cols-2">
                        <div class="flex flex-col justify-between gap-8 p-8 sm:p-10">
                            <div>
                                <x-ui.kicker tone="on-primary">Creators</x-ui.kicker>
                                <p class="mt-4 text-3xl tracking-tight">Get booked</p>
                            </div>
                            <x-ui.button href="{{ route('register.creator') }}" variant="inverted">Join as a creator</x-ui.button>
                        </div>
                        <div class="relative min-h-56">
                            <img
                                src="{{ $creators[0]['image'] }}"
                                alt=""
                                class="absolute inset-0 size-full object-cover"
                                loading="lazy"
                            >
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Pricing --}}
    <section id="pricing" class="scroll-mt-24 px-6 py-20 lg:px-12 xl:px-16" data-reveal>
        <h2 class="max-w-xl text-4xl font-normal tracking-tight sm:text-5xl">
            Two ways to
            <x-ui.em>work with us.</x-ui.em>
        </h2>
        <div class="mt-12 grid gap-5 lg:grid-cols-2">
            <x-ui.card flush class="rounded-3xl p-8">
                <x-ui.kicker>Self-serve</x-ui.kicker>
                <p class="mt-6 text-5xl tracking-tight">Free</p>
                <p class="mt-3 text-xl">Run it yourself</p>
                <div class="mt-8">
                    <x-ui.button href="{{ route('register.company') }}">Start free</x-ui.button>
                </div>
            </x-ui.card>
            <x-ui.card flush class="rounded-3xl p-8">
                <x-ui.kicker>Managed</x-ui.kicker>
                <p class="mt-6 text-5xl tracking-tight">Operated</p>
                <p class="mt-3 text-xl">Get your time back</p>
                <div class="mt-8">
                    <x-ui.button href="{{ route('register.company') }}" variant="secondary">Talk to us</x-ui.button>
                </div>
            </x-ui.card>
        </div>
    </section>

    {{-- FAQ --}}
    <section class="bg-foreground text-background" data-reveal>
        <div class="w-full px-6 py-20 lg:px-12 xl:px-16">
            <h2 class="text-4xl font-normal tracking-tight sm:text-5xl">
                FAQ
            </h2>
            <div class="mt-10 max-w-3xl">
                <x-ui.accordion-item name="faq" tone="on-inverse" :open="true" question="What is Naano?">
                    Naano is a B2B LinkedIn creator marketplace. Companies book vetted creators at a fixed price per post.
                </x-ui.accordion-item>
                <x-ui.accordion-item name="faq" tone="on-inverse" question="Who is this for?">
                    B2B teams that want posts from operators their buyers already follow, and creators who publish on LinkedIn.
                </x-ui.accordion-item>
                <x-ui.accordion-item name="faq" tone="on-inverse" question="How does pricing work?">
                    Creators set a net price per post. Companies book that rate. Upgrade later if you want Naano to run the channel.
                </x-ui.accordion-item>
                <x-ui.accordion-item name="faq" tone="on-inverse" question="What happens after signup?">
                    Verify your email, finish a short setup, then open your company or creator workspace.
                </x-ui.accordion-item>
            </div>
        </div>
    </section>

    {{-- Final CTA --}}
    <section class="px-6 py-20 lg:px-12 xl:px-16" data-reveal>
        <div class="relative w-full overflow-hidden rounded-3xl bg-accent">
            <div class="grid items-center lg:grid-cols-2">
                <div class="px-8 py-14 sm:px-12 sm:py-16 lg:px-16">
                    <h2 class="max-w-md text-4xl font-normal tracking-tight sm:text-5xl">
                        Your next campaign
                        <x-ui.em>starts here.</x-ui.em>
                    </h2>
                    <div class="mt-8 flex flex-wrap gap-3">
                        <x-ui.button href="{{ route('register.company') }}" size="lg">Get started</x-ui.button>
                        <x-ui.button href="{{ route('register.creator') }}" variant="secondary" size="lg">Join as a creator</x-ui.button>
                    </div>
                </div>
                <div class="relative hidden h-full min-h-72 lg:block">
                    <img
                        src="{{ $creators[3]['image'] }}"
                        alt=""
                        class="absolute inset-0 size-full object-cover"
                        loading="lazy"
                    >
                </div>
            </div>
        </div>
    </section>
</x-layouts.marketing>
