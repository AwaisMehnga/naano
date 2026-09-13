<form data-ajax data-icp-form method="POST" action="{{ route('onboarding.company.brief') }}" class="flex flex-col gap-5">
    @csrf

    <x-ui.field label="Value proposition" name="value_proposition">
        <x-ui.textarea
            id="value_proposition"
            name="value_proposition"
            rows="6"
            required
        >{{ old('value_proposition', $company->value_proposition) }}</x-ui.textarea>
    </x-ui.field>

    <div class="grid gap-5" data-icp-list>
        @foreach ($icps as $index => $icp)
            <x-ui.card class="p-3" data-icp-row>
                <div class="flex items-center justify-between gap-3">
                    <p class="text-xs text-muted-foreground">Audience</p>
                    <x-ui.button type="button" variant="link" data-remove-icp>
                        Remove
                    </x-ui.button>
                </div>
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
    </div>

    <template data-icp-template>
        <x-ui.card class="p-3" data-icp-row>
            <div class="flex items-center justify-between gap-3">
                <p class="text-xs text-muted-foreground">Audience</p>
                <x-ui.button type="button" variant="link" data-remove-icp>
                    Remove
                </x-ui.button>
            </div>
            <div class="mt-3 grid gap-5">
                <x-ui.field>
                    <x-ui.input
                        type="text"
                        name="icps[__INDEX__][title]"
                        required
                        placeholder="Audience name"
                    />
                </x-ui.field>
                <x-ui.field>
                    <x-ui.textarea
                        name="icps[__INDEX__][description]"
                        rows="3"
                        required
                        placeholder="Who they are, what they need"
                    ></x-ui.textarea>
                </x-ui.field>
            </div>
        </x-ui.card>
    </template>

    <x-ui.button type="button" variant="link" data-add-icp>
        Add an audience
    </x-ui.button>

    <div class="flex flex-wrap items-center gap-5">
        <x-ui.button>
            Continue
        </x-ui.button>
        <x-ui.button href="{{ route('onboarding.company', ['step' => 'website']) }}" variant="link">
            Back to website
        </x-ui.button>
    </div>
</form>
