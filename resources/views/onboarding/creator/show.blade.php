@php
    $steps = [
        'linkedin' => '2 / 4',
        'industries' => '3 / 4',
        'offer' => '4 / 4',
        'professional' => 'Optional',
    ];
    $headings = [
        'linkedin' => 'Add your LinkedIn',
        'industries' => 'Your industries',
        'offer' => 'Set your price',
        'professional' => 'Enter your workspace',
    ];
@endphp

<x-layouts.guest
    title="Creator setup"
    heading="{{ $headings[$step] }}"
    description="{{ $steps[$step] }}"
    ajax
    wide
>
    <p id="form-errors" class="mb-4 hidden text-sm text-destructive"></p>

    @include('onboarding.creator.steps.'.$step)

    <x-slot:panel>
        <div class="rounded-xl border border-border bg-card p-6">
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
        </div>
    </x-slot:panel>
</x-layouts.guest>
