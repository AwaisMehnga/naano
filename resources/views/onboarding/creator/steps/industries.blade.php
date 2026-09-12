<form data-ajax method="POST" action="{{ route('onboarding.creator.industries') }}" class="flex flex-col gap-5">
    @csrf

    <p class="text-sm text-muted-foreground">Pick up to 3.</p>

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
    @error('industries')
        <p class="text-sm text-destructive">{{ $message }}</p>
    @enderror

    <button type="submit" class="inline-flex w-full items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:opacity-90">
        Continue
    </button>
</form>
