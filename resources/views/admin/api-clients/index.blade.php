@extends('layouts.admin-app')

@section('title', 'API clients')
@section('heading', 'API clients')

@section('heading-icon')
    <x-icon name="lucide:key-round" width="1.05rem" height="1.05rem" />
@endsection

@section('content')
    <div class="admin-toolbar">
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
                        <th>Secret</th>
                        <th>Created</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($apiClients as $apiClient)
                        <tr>
                            <td><strong>{{ $apiClient->name }}</strong></td>
                            <td><code>{{ $apiClient->client_id }}</code></td>
                            <td><code>{{ $apiClient->secret }}</code></td>
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
                            <td colspan="5" class="admin-table-empty">No API clients found.</td>
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
