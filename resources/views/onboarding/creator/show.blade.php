@php
    $stepIndex = match ($step) {
        'linkedin' => 1,
        'industries' => 2,
        'offer' => 3,
        default => 4,
    };
    $steps = ['LinkedIn', 'Industries', 'Price', 'Workspace'];
    $headings = [
        'linkedin' => 'Add your LinkedIn',
        'industries' => 'Your industries',
        'offer' => 'Set your price',
        'professional' => 'Enter your workspace',
    ];
@endphp

<x-layouts.onboarding
    title="Creator setup"
    heading="{{ $headings[$step] }}"
    :step="$stepIndex"
    :steps="$steps"
>
    <x-ui.alert variant="danger" id="form-errors" class="mb-4 hidden"></x-ui.alert>

    @include('onboarding.creator.steps.'.$step)

    <x-slot:panel>
        <x-ui.card>
            <p class="text-xs uppercase tracking-wide text-muted-foreground">Marketplace card</p>
            <p class="mt-4 text-lg font-medium">{{ $user->name }}</p>
            <p class="mt-1 text-sm text-muted-foreground">{{ $profile->headline ?: 'Your headline' }}</p>
            <div class="mt-6 grid grid-cols-2 gap-4 text-sm">
                <div>
                    <p class="text-muted-foreground">Country</p>
                    <p class="font-medium">{{ $countries[$profile->country] ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-muted-foreground">Per post</p>
                    <p class="font-medium">{{ $profile->price_cents ? '€'.number_format($profile->price_cents / 100, 0) : '—' }}</p>
                </div>
            </div>
        </x-ui.card>
    </x-slot:panel>
</x-layouts.onboarding>
