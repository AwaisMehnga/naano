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
        'linkedin' => 'We confirm ownership, then sync posts and audience.',
        'industries' => 'Pick up to three so brands can find you.',
        'offer' => 'Net price brands see when they book a post.',
        'professional' => 'You’re ready. Open the creator workspace.',
    ];
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
        <p class="mt-5 text-title font-medium tracking-tight">{{ $user->name }}</p>
        <p class="mt-2 text-body text-primary-foreground/80">{{ $profile->headline ?: 'Headline appears after verify.' }}</p>
        <dl class="mt-8 grid grid-cols-2 gap-5 text-sm">
            <div>
                <dt class="text-primary-foreground/60">Country</dt>
                <dd class="mt-1 font-medium">{{ $countries[$profile->country] ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-primary-foreground/60">Per post</dt>
                <dd class="mt-1 font-medium">{{ $profile->price_cents ? '€'.number_format($profile->price_cents / 100, 0) : '—' }}</dd>
            </div>
        </dl>
    </x-slot:panel>
</x-layouts.onboarding>
