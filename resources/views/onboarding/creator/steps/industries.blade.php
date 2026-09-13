<form data-ajax method="POST" action="{{ route('onboarding.creator.industries') }}" class="flex flex-col gap-5">
    @csrf

    <x-ui.field name="industries" hint="Pick up to 3.">
        <div class="flex flex-wrap gap-2" data-industry-group>
            @foreach ($industries as $industry)
                <label class="cursor-pointer">
                    <input
                        type="checkbox"
                        name="industries[]"
                        value="{{ $industry }}"
                        class="peer sr-only"
                        @checked(in_array($industry, old('industries', $profile->industries ?? []), true))
                    >
                    <span class="inline-flex rounded-full border border-border bg-card px-3 py-1 text-sm peer-checked:border-primary peer-checked:bg-primary peer-checked:text-primary-foreground">
                        {{ $industry }}
                    </span>
                </label>
            @endforeach
        </div>
    </x-ui.field>

    <x-ui.button>
        Continue
    </x-ui.button>
</form>
