@props([
    'type' => 'success',
])

@php
    $icons = [
        'success' => 'lucide:circle-check',
        'error' => 'lucide:circle-alert',
        'warning' => 'lucide:triangle-alert',
        'info' => 'lucide:info',
    ];

    $icon = $icons[$type] ?? $icons['info'];
@endphp

<div {{ $attributes->class(['admin-alert', 'admin-alert-'.$type]) }} role="alert">
    <x-icon :name="$icon" />
    <span>{{ $slot }}</span>
</div>
