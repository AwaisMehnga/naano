<form data-ajax method="POST" action="{{ route('onboarding.creator.complete') }}" class="flex flex-col gap-5">
    @csrf

    <p class="text-sm text-muted-foreground">Professional details can wait. Finish them before you invoice or withdraw.</p>

    <x-ui.button>
        Go to workspace
    </x-ui.button>
</form>
