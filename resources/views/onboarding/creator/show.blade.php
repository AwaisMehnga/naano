@php
    $stepIndex = match ($step) {
        'linkedin' => 1,
        'industries' => 2,
        'offer' => 3,
        default => 4,
    };
    $steps = ['LinkedIn', 'Industries', 'Price', 'Workspace'];
    $headings = [
        'linkedin' => 'Verify your LinkedIn',
        'industries' => 'Your industries',
        'offer' => 'Set your price',
        'professional' => 'Enter your workspace',
    ];
    $descriptions = [
        'linkedin' => 'We confirm ownership and pull your public profile.',
        'industries' => 'Pick up to three so brands can find you.',
        'offer' => 'Net price brands see when they book a post.',
        'professional' => 'You’re ready. Open the creator workspace.',
    ];
    $linkedin = is_array($profile->linkedin_profile) ? $profile->linkedin_profile : [];
    $cardName = $profile->display_name ?: $user->name;
    $photoUrl = \App\Support\PublicDisk::url($profile->photo_path)
        ?? (is_string($linkedin['picture_url'] ?? null) ? $linkedin['picture_url'] : null);
    $followers = is_int($linkedin['follower_count'] ?? null) ? $linkedin['follower_count'] : null;
    $companyName = is_array($linkedin['current_company'] ?? null)
        ? ($linkedin['current_company']['name'] ?? null)
        : null;
    $location = is_string($linkedin['location'] ?? null) && $linkedin['location'] !== ''
        ? $linkedin['location']
        : null;
@endphp

<x-layouts.onboarding
    title="Creator setup"
    heading="{{ $headings[$step] }}"
    description="{{ $descriptions[$step] }}"
    :step="$stepIndex"
    :steps="$steps"
>
    <x-ui.alert variant="danger" id="form-errors" class="mb-4 hidden"></x-ui.alert>

    @include('onboarding.creator.steps.'.$step)

    <x-slot:panel>
        <x-ui.kicker tone="on-primary">Marketplace card</x-ui.kicker>

        <div class="mt-6 flex items-center gap-4">
            @if ($photoUrl)
                <img
                    src="{{ $photoUrl }}"
                    alt=""
                    class="size-16 shrink-0 rounded-full object-cover ring-2 ring-primary-foreground/20"
                >
            @else
                <div class="flex size-16 shrink-0 items-center justify-center rounded-full bg-primary-foreground/15 text-lg font-medium">
                    {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($cardName, 0, 1)) }}
                </div>
            @endif
            <div class="min-w-0">
                <p class="text-title font-medium tracking-tight">{{ $cardName }}</p>
                @if ($profile->isLinkedInVerified())
                    <p class="mt-1 text-xs text-primary-foreground/60">LinkedIn verified</p>
                @endif
            </div>
        </div>

        <p class="mt-4 text-body text-primary-foreground/80">
            {{ $profile->headline ?: 'Headline appears after verify.' }}
        </p>

        @if ($companyName || $location)
            <p class="mt-3 text-sm text-primary-foreground/70">
                {{ collect([$companyName, $location])->filter()->implode(' · ') }}
            </p>
        @endif

        <dl class="mt-8 grid grid-cols-2 gap-5 text-sm">
            <div>
                <dt class="text-primary-foreground/60">Country</dt>
                <dd class="mt-1 font-medium">{{ $countries[$profile->country] ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-primary-foreground/60">Per post</dt>
                <dd class="mt-1 font-medium">{{ $profile->price_cents ? '€'.number_format($profile->price_cents / 100, 0) : '—' }}</dd>
            </div>
            @if ($followers !== null)
                <div>
                    <dt class="text-primary-foreground/60">Followers</dt>
                    <dd class="mt-1 font-medium">{{ number_format($followers) }}</dd>
                </div>
            @endif
            @if ($profile->industries)
                <div>
                    <dt class="text-primary-foreground/60">Industries</dt>
                    <dd class="mt-1 font-medium">{{ collect($profile->industries)->take(2)->implode(', ') }}</dd>
                </div>
            @endif
        </dl>
    </x-slot:panel>
</x-layouts.onboarding>
