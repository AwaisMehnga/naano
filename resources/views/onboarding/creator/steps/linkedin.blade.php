@if ($profile->linkedin_verify_code && ! $profile->linkedin_verified_at)
    <div class="flex flex-col gap-5">
        <x-ui.card class="bg-lime-soft/50">
            <p class="text-sm text-muted-foreground">Add this code at the <strong class="text-foreground">end</strong> of your LinkedIn headline, save on LinkedIn, then verify.</p>
            <p class="mt-3 font-mono text-2xl font-semibold tracking-widest">{{ $profile->linkedin_verify_code }}</p>
            <p class="mt-2 text-xs text-muted-foreground">Example: Your headline here {{ $profile->linkedin_verify_code }}</p>
            <p class="mt-2 text-xs text-muted-foreground break-all">{{ $profile->linkedin_url }}</p>
        </x-ui.card>

        <form
            data-ajax
            data-analyze
            data-wait-posts
            data-loading-label="Checking LinkedIn…"
            method="POST"
            action="{{ route('onboarding.creator.linkedin.verify') }}"
            class="flex flex-col gap-4"
        >
            @csrf

            <div data-analyze-status class="hidden space-y-2 rounded-2xl bg-muted/60 p-4 text-sm">
                <p data-check class="hidden text-muted-foreground">Fetching your public profile…</p>
                <p data-check class="hidden text-muted-foreground">Looking for {{ $profile->linkedin_verify_code }} in your headline…</p>
                <p data-check class="hidden text-muted-foreground">Saving profile insights…</p>
            </div>

            <div data-posts-sync class="hidden space-y-2 rounded-2xl bg-muted/60 p-4 text-sm">
                <p class="font-medium text-foreground">Syncing posts…</p>
                <p data-posts-sync-message class="text-muted-foreground">Pulling your recent LinkedIn posts in the background.</p>
            </div>

            <x-ui.button>
                Verify LinkedIn
            </x-ui.button>
        </form>

        <form data-ajax method="POST" action="{{ route('onboarding.creator.linkedin') }}" enctype="multipart/form-data" class="flex flex-col gap-4 border-t border-border pt-5">
            @csrf
            <p class="text-sm text-muted-foreground">Wrong URL? Restart with a new code.</p>

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

            <x-ui.field label="Country" name="country">
                <x-ui.select id="country" name="country" required>
                    <option value="">Select</option>
                    @foreach ($countries as $code => $name)
                        <option value="{{ $code }}" @selected(old('country', $profile->country) === $code)>{{ $name }}</option>
                    @endforeach
                </x-ui.select>
            </x-ui.field>

            <x-ui.button variant="secondary">
                Get a new code
            </x-ui.button>
        </form>
    </div>
@else
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

        <p class="text-sm text-muted-foreground">
            We’ll give you a short code to add at the end of your LinkedIn headline so we can confirm the profile is yours.
        </p>

        <x-ui.button>
            Get verification code
        </x-ui.button>
    </form>
@endif
