<form data-ajax method="POST" action="{{ route('onboarding.creator.linkedin') }}" enctype="multipart/form-data" class="flex flex-col gap-5">
    @csrf

    <x-ui.field label="Public LinkedIn URL" name="linkedin_url">
        <x-ui.input
            id="linkedin_url"
            type="url"
            name="linkedin_url"
            value="{{ old('linkedin_url', $profile->linkedin_url) }}"
            required
            placeholder="https://www.linkedin.com/in/you"
        />
    </x-ui.field>

    <x-ui.field label="Headline" name="headline">
        <x-ui.input
            id="headline"
            type="text"
            name="headline"
            value="{{ old('headline', $profile->headline) }}"
            required
            maxlength="255"
            placeholder="What you do, for whom"
        />
    </x-ui.field>

    <x-ui.field label="Country" name="country">
        <x-ui.select id="country" name="country" required>
            <option value="">Select</option>
            @foreach ($countries as $code => $name)
                <option value="{{ $code }}" @selected(old('country', $profile->country) === $code)>{{ $name }}</option>
            @endforeach
        </x-ui.select>
    </x-ui.field>

    <x-ui.field label="Photo" name="photo" hint="Optional">
        <x-ui.input
            id="photo"
            type="file"
            name="photo"
            accept="image/jpeg,image/png,image/webp"
        />
    </x-ui.field>

    <x-ui.button>
        Continue
    </x-ui.button>
</form>
