@extends('layouts.admin-app')

@section('title', 'Edit API client')
@section('heading', 'Edit API client')

@section('heading-icon')
    <x-icon name="lucide:key-round" width="1.05rem" height="1.05rem" />
@endsection

@section('content')
    <a href="{{ route('admin.api-clients.index') }}" class="admin-back-link">
        <x-icon name="lucide:arrow-left" width="1rem" height="1rem" />
        <span>Back to API clients</span>
    </a>

    <div class="admin-panel admin-form">
        <div class="admin-panel-body">
            <div class="admin-field">
                <label>Client ID</label>
                <p><code class="admin-break">{{ $apiClient->client_id }}</code></p>
            </div>

            <div class="admin-field">
                <label>Secret</label>
                <p><code class="admin-break">{{ $apiClient->secret }}</code></p>
            </div>

            <form method="POST" action="{{ route('admin.api-clients.update', $apiClient) }}">
                @method('PUT')
                @include('admin.api-clients._form', ['apiClient' => $apiClient])

                <div class="admin-form-actions">
                    <x-admin.button type="submit" icon="lucide:check">
                        Save changes
                    </x-admin.button>

                    <a href="{{ route('admin.api-clients.index') }}" class="admin-cancel-link">Cancel</a>
                </div>
            </form>

            <form
                method="POST"
                action="{{ route('admin.api-clients.regenerate', $apiClient) }}"
                onsubmit="return confirm('Regenerate this secret? The current secret will stop working.')"
            >
                @csrf
                <x-admin.button type="submit" variant="warning" icon="lucide:refresh-cw">
                    Regenerate secret
                </x-admin.button>
            </form>
        </div>
    </div>
@endsection
