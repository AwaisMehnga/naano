<form data-ajax method="POST" action="{{ route('onboarding.company.brief') }}" class="flex flex-col gap-5">
    @csrf

    <div class="grid gap-2">
        <label for="value_proposition" class="text-sm font-medium">Value proposition</label>
        <textarea
            id="value_proposition"
            name="value_proposition"
            rows="6"
            required
            class="w-full rounded-md border border-input bg-card px-3 py-2 text-sm shadow-sm outline-none focus:border-ring focus:ring-2 focus:ring-ring/30"
        >{{ old('value_proposition', $company->value_proposition) }}</textarea>
        @error('value_proposition')
            <p class="text-sm text-destructive">{{ $message }}</p>
        @enderror
    </div>

    @foreach ($icps as $index => $icp)
        <div class="grid gap-2 rounded-lg border border-border p-3">
            <p class="text-xs text-muted-foreground">ICP {{ $index + 1 }}</p>
            <input
                type="text"
                name="icps[{{ $index }}][title]"
                value="{{ $icp['title'] ?? '' }}"
                required
                placeholder="Audience name"
                class="w-full rounded-md border border-input bg-card px-3 py-2 text-sm shadow-sm outline-none focus:border-ring focus:ring-2 focus:ring-ring/30"
            >
            <textarea
                name="icps[{{ $index }}][description]"
                rows="3"
                required
                placeholder="Who they are, what they need"
                class="w-full rounded-md border border-input bg-card px-3 py-2 text-sm shadow-sm outline-none focus:border-ring focus:ring-2 focus:ring-ring/30"
            >{{ $icp['description'] ?? '' }}</textarea>
        </div>
    @endforeach

    <button type="submit" class="inline-flex w-full items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:opacity-90">
        Continue to AI Matching
    </button>
</form>
