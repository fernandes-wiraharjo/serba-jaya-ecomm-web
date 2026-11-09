@props([
    'name',
    'width' => '1.15rem',
    'height' => '1.15rem',
])

<iconify-icon
    icon="{{ $name }}"
    width="{{ $width }}"
    height="{{ $height }}"
    {{ $attributes->class(['inline-block shrink-0 align-middle']) }}
></iconify-icon>
