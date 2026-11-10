@extends('layouts.admin-app')

@section('title', 'API clients')
@section('heading', 'API clients')

@section('heading-icon')
    <x-icon name="lucide:key-round" width="1.35rem" height="1.35rem" />
@endsection

@section('content')
    @include('admin.api-clients._secret-warning')

    @php
        /** @var array{client_id: string, secret: string}|null $credentials */
        $credentials = session('api_client_credentials');
    @endphp

    @if (is_array($credentials))
        <div class="admin-panel" style="margin-bottom: 1rem;">
            <div class="admin-panel-body">
                <p>Copy these credentials now. The secret is not shown again.</p>
                <p><strong>Client ID</strong><br><code style="word-break: break-all;">{{ $credentials['client_id'] }}</code></p>
                <p><strong>Secret</strong><br><code style="word-break: break-all;">{{ $credentials['secret'] }}</code></p>
            </div>
        </div>
    @endif

    <div class="admin-toolbar">
        <p>Every API request sends <code>X-Client-ID</code>, <code>X-Nonce</code> (a random string unique on each request to the same endpoint), and <code>X-Api-Signature</code>. The signature is sha256 of the payload values, in alphabetical key order with nulls omitted, then the nonce, then the secret. With no payload, hash the nonce and secret only. Each accepted signature can be used once, then it is rejected for one hour. Regenerating or deleting a client drops its cached secret immediately.</p>

        <x-admin.button
            href="{{ route('admin.api-clients.create') }}"
            icon="lucide:plus"
            block
        >
            Add API client
        </x-admin.button>
    </div>

    <div class="admin-panel">
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Client ID</th>
                        <th>Created</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($apiClients as $apiClient)
                        <tr>
                            <td><strong>{{ $apiClient->name }}</strong></td>
                            <td><code>{{ $apiClient->client_id }}</code></td>
                            <td>{{ $apiClient->created_at?->format('M j, Y') }}</td>
                            <td>
                                <div class="admin-actions">
                                    <x-admin.button
                                        href="{{ route('admin.api-clients.edit', $apiClient) }}"
                                        variant="warning"
                                        icon="lucide:pencil"
                                        icon-width="1rem"
                                        icon-height="1rem"
                                    >
                                        Edit
                                    </x-admin.button>

                                    <form
                                        method="POST"
                                        action="{{ route('admin.api-clients.regenerate', $apiClient) }}"
                                        onsubmit="return confirm('Regenerate this secret? The current secret will stop working.')"
                                    >
                                        @csrf
                                        <x-admin.button
                                            type="submit"
                                            variant="warning"
                                            icon="lucide:refresh-cw"
                                            icon-width="1rem"
                                            icon-height="1rem"
                                        >
                                            Regenerate
                                        </x-admin.button>
                                    </form>

                                    <form
                                        method="POST"
                                        action="{{ route('admin.api-clients.destroy', $apiClient) }}"
                                        onsubmit="return confirm('Delete this API client?')"
                                    >
                                        @csrf
                                        @method('DELETE')
                                        <x-admin.button
                                            type="submit"
                                            variant="danger"
                                            icon="lucide:trash-2"
                                            icon-width="1rem"
                                            icon-height="1rem"
                                        >
                                            Delete
                                        </x-admin.button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="admin-table-empty">No API clients found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($apiClients->hasPages())
            <div class="admin-pagination">
                {{ $apiClients->links() }}
            </div>
        @endif
    </div>
@endsection
