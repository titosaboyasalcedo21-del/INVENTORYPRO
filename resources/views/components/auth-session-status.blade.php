@props(['status'])

@if ($status)
    <div {{ $attributes->class(['alert alert-success py-2 px-3 small']) }} role="status">
        <i class="bi bi-check-circle me-1" aria-hidden="true"></i>{{ $status }}
    </div>
@endif
