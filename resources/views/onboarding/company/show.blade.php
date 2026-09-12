@php
    $headings = [
        'website' => 'Your website',
        'brief' => 'Value prop & ICP',
    ];
    $descriptions = [
        'website' => 'We’ll read the site and draft a brief.',
        'brief' => 'Edit once. Creators get this brief.',
    ];
    $icps = old('icps', $company->icps ?? [
        ['title' => '', 'description' => ''],
        ['title' => '', 'description' => ''],
        ['title' => '', 'description' => ''],
    ]);
@endphp

<x-layouts.guest
    title="Company setup"
    heading="{{ $headings[$step] }}"
    description="{{ $descriptions[$step] }}"
    ajax
    wide
>
    <p id="form-errors" class="mb-4 hidden text-sm text-destructive"></p>

    @include('onboarding.company.steps.'.$step, ['icps' => $icps])

    <x-slot:panel>
        <div class="rounded-xl border border-border bg-card p-6">
            <p class="text-xs uppercase tracking-wide text-muted-foreground">Starter brief</p>
            <p class="mt-4 text-sm">{{ \Illuminate\Support\Str::limit($company->value_proposition, 140) ?: 'Product and audience appear here.' }}</p>
            <ul class="mt-4 space-y-2 text-sm">
                @forelse (($company->icps ?? []) as $icp)
                    <li class="rounded-md bg-muted px-3 py-2">{{ $icp['title'] }}</li>
                @empty
                    <li class="text-muted-foreground">3 ICPs after analyze</li>
                @endforelse
            </ul>
        </div>
    </x-slot:panel>
</x-layouts.guest>
