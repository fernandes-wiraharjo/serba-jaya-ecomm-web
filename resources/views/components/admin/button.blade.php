@props([
    'variant' => 'primary',
    'href' => null,
    'type' => 'button',
    'icon' => null,
    'iconWidth' => '1.15rem',
    'iconHeight' => '1.15rem',
    'block' => false,
])

@php
    $classes = [
        'admin-btn',
        'admin-btn-'.$variant,
        $block ? 'admin-btn-block' : null,
    ];
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>
        @if ($icon)
            <x-icon :name="$icon" :width="$iconWidth" :height="$iconHeight" />
        @endif
        @if ($slot->isNotEmpty())
            <span>{{ $slot }}</span>
        @endif
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->class($classes) }}>
        @if ($icon)
            <x-icon :name="$icon" :width="$iconWidth" :height="$iconHeight" />
        @endif
        @if ($slot->isNotEmpty())
            <span>{{ $slot }}</span>
        @endif
    </button>
@endif
