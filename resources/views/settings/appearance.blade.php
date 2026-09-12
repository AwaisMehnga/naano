<x-layouts.settings title="Appearance settings">
    <h2 class="mb-1 text-lg font-medium">Appearance settings</h2>
    <p class="mb-6 text-sm text-muted-foreground">Update the appearance settings for your account</p>

    <form method="POST" action="{{ route('appearance.update') }}" class="grid max-w-lg gap-3">
        @csrf
        @method('PUT')

        @foreach (['light' => 'Light', 'dark' => 'Dark', 'system' => 'System'] as $value => $label)
            <label class="flex items-center gap-2 text-sm">
                <input type="radio" name="appearance" value="{{ $value }}" @checked(old('appearance', $appearance) === $value)>
                {{ $label }}
            </label>
        @endforeach

        <button type="submit" class="mt-2 inline-flex w-fit items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:opacity-90">
            Save
        </button>
    </form>
</x-layouts.settings>
