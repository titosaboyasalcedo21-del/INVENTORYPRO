@props(['disabled' => false])

@php($hasError = $errors->has($attributes->get('name')))

<input @disabled($disabled) {{ $attributes->class(['form-control', 'is-invalid' => $hasError]) }}>
