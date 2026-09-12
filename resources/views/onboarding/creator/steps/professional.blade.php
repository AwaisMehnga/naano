<form data-ajax method="POST" action="{{ route('onboarding.creator.complete') }}" class="flex flex-col gap-5">
    @csrf

    <p class="text-sm text-muted-foreground">Professional details can wait. Finish them before you invoice or withdraw.</p>

    <button type="submit" class="inline-flex w-full items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:opacity-90">
        Go to my workspace
    </button>
</form>
