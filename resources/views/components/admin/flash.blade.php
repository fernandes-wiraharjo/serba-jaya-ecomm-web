@php
    $flashes = [
        'success' => session('success') ?? session('status'),
        'error' => session('error'),
        'warning' => session('warning'),
        'info' => session('info'),
    ];
@endphp

@foreach ($flashes as $type => $message)
    @if (filled($message))
        <x-admin.alert :type="$type">{{ $message }}</x-admin.alert>
    @endif
@endforeach
