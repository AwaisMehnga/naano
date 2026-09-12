<form data-ajax data-analyze method="POST" action="{{ route('onboarding.company.website') }}" class="flex flex-col gap-5">
    @csrf

    <div class="grid gap-2">
        <label for="website" class="text-sm font-medium">Website</label>
        <input
            id="website"
            type="url"
            name="website"
            value="{{ old('website', $company->website) }}"
            required
            placeholder="https://yourcompany.com"
            class="w-full rounded-md border border-input bg-card px-3 py-2 text-sm shadow-sm outline-none focus:border-ring focus:ring-2 focus:ring-ring/30"
        >
        @error('website')
            <p class="text-sm text-destructive">{{ $message }}</p>
        @enderror
    </div>

    <div data-analyze-status class="hidden space-y-2 text-sm">
        <p data-check>Reading your website…</p>
        <p data-check>Extracting product signals…</p>
        <p data-check>Identifying your ICP…</p>
        <p data-check>Preparing your brief…</p>
    </div>

    <button type="submit" class="inline-flex w-full items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:opacity-90">
        Analyze my website
    </button>
</form>
