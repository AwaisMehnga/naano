<form data-ajax data-analyze method="POST" action="{{ route('onboarding.company.website') }}" class="flex flex-col gap-5">
    @csrf

    <x-ui.field label="Website" name="website">
        <x-ui.input
            id="website"
            type="url"
            name="website"
            value="{{ old('website', $company->website) }}"
            required
            placeholder="https://yourcompany.com"
        />
    </x-ui.field>

    <div data-analyze-status class="hidden space-y-2 text-sm">
        <p data-check class="hidden text-muted-foreground">Reading your website…</p>
        <p data-check class="hidden text-muted-foreground">Extracting product signals…</p>
        <p data-check class="hidden text-muted-foreground">Finding audiences on the page…</p>
        <p data-check class="hidden text-muted-foreground">Preparing your brief…</p>
    </div>

    <x-ui.button>
        Analyze website
    </x-ui.button>
</form>
