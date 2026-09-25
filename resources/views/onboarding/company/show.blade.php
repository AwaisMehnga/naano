@php
    $stepIndex = $step === 'brief' ? 2 : 1;
    $steps = ['Website', 'Brief'];
    $headings = [
        'website' => 'Add your website',
        'brief' => 'Value prop & ICP',
    ];
    $descriptions = [
        'website' => 'We’ll draft a brief from the page — edit it next.',
        'brief' => 'Tighten the draft, then open your workspace.',
    ];
    $icps = old('icps', $company->icps ?: [
        ['title' => '', 'description' => ''],
    ]);
@endphp

<x-layouts.onboarding
    title="Company setup"
    :heading="$headings[$step]"
    :description="$descriptions[$step]"
    :step="$stepIndex"
    :steps="$steps"
>
    <x-ui.alert variant="danger" id="form-errors" class="mb-4 hidden"></x-ui.alert>

    @include('onboarding.company.steps.'.$step, ['icps' => $icps])

    <x-slot:panel>
        <x-ui.kicker tone="on-primary">Starter brief</x-ui.kicker>
        <p class="mt-5 text-body leading-relaxed text-primary-foreground/80">
            {{ \Illuminate\Support\Str::limit($company->value_proposition, 160) ?: 'Value prop appears after we read your site.' }}
        </p>
        <ul class="mt-6 space-y-2">
            @forelse (($company->icps ?? []) as $icp)
                <li class="rounded-2xl bg-primary-foreground/10 px-4 py-3 text-sm">{{ $icp['title'] }}</li>
            @empty
                <li class="text-sm text-primary-foreground/60">Audiences appear after analysis.</li>
            @endforelse
        </ul>
    </x-slot:panel>
</x-layouts.onboarding>
