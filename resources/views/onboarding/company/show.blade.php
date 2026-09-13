@php
    $stepIndex = $step === 'brief' ? 2 : 1;
    $steps = ['Website', 'Brief'];
    $headings = [
        'website' => 'Add your website',
        'brief' => 'Value prop & ICP',
    ];
    $icps = old('icps', $company->icps ?: [
        ['title' => '', 'description' => ''],
    ]);
@endphp

<x-layouts.onboarding
    title="Company setup"
    :heading="$headings[$step]"
    :step="$stepIndex"
    :steps="$steps"
>
    <x-ui.alert variant="danger" id="form-errors" class="mb-4 hidden"></x-ui.alert>

    @include('onboarding.company.steps.'.$step, ['icps' => $icps])

    <x-slot:panel>
        <x-ui.card>
            <p class="text-xs uppercase tracking-wide text-muted-foreground">Starter brief</p>
            <p class="mt-4 text-sm">{{ \Illuminate\Support\Str::limit($company->value_proposition, 140) ?: 'Product and audience appear here.' }}</p>
            <ul class="mt-4 space-y-2 text-sm">
                @forelse (($company->icps ?? []) as $icp)
                    <li class="rounded-md bg-muted px-3 py-2">{{ $icp['title'] }}</li>
                @empty
                    <li class="text-muted-foreground">Audiences appear after we read the site.</li>
                @endforelse
            </ul>
        </x-ui.card>
    </x-slot:panel>
</x-layouts.onboarding>
