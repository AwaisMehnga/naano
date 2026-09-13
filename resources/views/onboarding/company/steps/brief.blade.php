<form data-ajax method="POST" action="{{ route('onboarding.company.brief') }}" class="flex flex-col gap-5">
    @csrf

    <x-ui.field label="Value proposition" name="value_proposition">
        <x-ui.textarea
            id="value_proposition"
            name="value_proposition"
            rows="6"
            required
        >{{ old('value_proposition', $company->value_proposition) }}</x-ui.textarea>
    </x-ui.field>

    @foreach ($icps as $index => $icp)
        <x-ui.card class="p-3">
            <p class="text-xs text-muted-foreground">ICP {{ $index + 1 }}</p>
            <div class="mt-3 grid gap-5">
                <x-ui.field name="icps.{{ $index }}.title">
                    <x-ui.input
                        type="text"
                        name="icps[{{ $index }}][title]"
                        value="{{ $icp['title'] ?? '' }}"
                        required
                        placeholder="Audience name"
                    />
                </x-ui.field>
                <x-ui.field name="icps.{{ $index }}.description">
                    <x-ui.textarea
                        name="icps[{{ $index }}][description]"
                        rows="3"
                        required
                        placeholder="Who they are, what they need"
                    >{{ $icp['description'] ?? '' }}</x-ui.textarea>
                </x-ui.field>
            </div>
        </x-ui.card>
    @endforeach

    <x-ui.button>
        Continue
    </x-ui.button>
</form>
