<textarea
    {{ $attributes->merge(['class' => 'w-full rounded-md border border-input bg-card px-3 py-2 text-sm outline-none focus:border-ring focus:ring-2 focus:ring-ring/30']) }}
>{{ $slot }}</textarea>
