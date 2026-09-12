<x-layouts.guest
    title="Email verification"
    heading="Email verification"
    description="Please verify your email address by clicking on the link we just emailed to you."
>
    @if ($status === 'verification-link-sent')
        <p class="mb-4 text-center text-sm font-medium text-primary">
            A new verification link has been sent to the email address you provided during registration.
        </p>
    @endif

    <form method="POST" action="{{ route('verification.send') }}" class="space-y-6 text-center">
        @csrf
        <button
            type="submit"
            class="inline-flex w-full items-center justify-center rounded-md bg-secondary px-4 py-2 text-sm font-medium text-secondary-foreground hover:opacity-90"
        >
            Resend verification email
        </button>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="mt-4 text-center">
        @csrf
        <button type="submit" class="text-sm text-primary underline-offset-4 hover:underline">
            Log out
        </button>
    </form>
</x-layouts.guest>
