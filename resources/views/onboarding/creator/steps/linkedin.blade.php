<form data-ajax method="POST" action="{{ route('onboarding.creator.linkedin') }}" enctype="multipart/form-data" class="flex flex-col gap-5">
    @csrf

    <div class="grid gap-2">
        <label for="linkedin_url" class="text-sm font-medium">Public LinkedIn URL</label>
        <input
            id="linkedin_url"
            type="url"
            name="linkedin_url"
            value="{{ old('linkedin_url', $profile->linkedin_url) }}"
            required
            placeholder="https://www.linkedin.com/in/you"
            class="w-full rounded-md border border-input bg-card px-3 py-2 text-sm shadow-sm outline-none focus:border-ring focus:ring-2 focus:ring-ring/30"
        >
        @error('linkedin_url')
            <p class="text-sm text-destructive">{{ $message }}</p>
        @enderror
    </div>

    <div class="grid gap-2">
        <label for="headline" class="text-sm font-medium">Headline</label>
        <input
            id="headline"
            type="text"
            name="headline"
            value="{{ old('headline', $profile->headline) }}"
            required
            maxlength="255"
            placeholder="What you do, for whom"
            class="w-full rounded-md border border-input bg-card px-3 py-2 text-sm shadow-sm outline-none focus:border-ring focus:ring-2 focus:ring-ring/30"
        >
        @error('headline')
            <p class="text-sm text-destructive">{{ $message }}</p>
        @enderror
    </div>

    <div class="grid gap-2">
        <label for="country" class="text-sm font-medium">Country</label>
        <select
            id="country"
            name="country"
            required
            class="w-full rounded-md border border-input bg-card px-3 py-2 text-sm shadow-sm outline-none focus:border-ring focus:ring-2 focus:ring-ring/30"
        >
            <option value="">Select</option>
            @foreach ($countries as $code => $name)
                <option value="{{ $code }}" @selected(old('country', $profile->country) === $code)>{{ $name }}</option>
            @endforeach
        </select>
        @error('country')
            <p class="text-sm text-destructive">{{ $message }}</p>
        @enderror
    </div>

    <div class="grid gap-2">
        <label for="photo" class="text-sm font-medium">Photo <span class="font-normal text-muted-foreground">(optional)</span></label>
        <input
            id="photo"
            type="file"
            name="photo"
            accept="image/jpeg,image/png,image/webp"
            class="w-full text-sm"
        >
        @error('photo')
            <p class="text-sm text-destructive">{{ $message }}</p>
        @enderror
    </div>

    <button type="submit" class="inline-flex w-full items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:opacity-90">
        Continue
    </button>
</form>
