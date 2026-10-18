@props(['messages'])

@if ($messages)
    <ul {{ $attributes->class(['invalid-feedback d-block list-unstyled mb-0']) }}>
        @foreach ((array) $messages as $message)
            <li>{{ $message }}</li>
        @endforeach
    </ul>
@endif
