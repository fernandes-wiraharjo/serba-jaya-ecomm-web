@extends('layouts.admin-app')

@section('title', 'Admins')
@section('heading', 'Admins')

@section('heading-icon')
    <x-icon name="lucide:shield-user" width="1.35rem" height="1.35rem" />
@endsection

@section('content')
    <div class="admin-toolbar">
        <p>Manage admin accounts that can access this panel.</p>

        <x-admin.button
            href="{{ route('admin.admins.create') }}"
            icon="lucide:plus"
            block
        >
            Add admin
        </x-admin.button>
    </div>

    <div class="admin-panel">
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Created</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($admins as $managedAdmin)
                        <tr>
                            <td>
                                <strong>{{ $managedAdmin->name }}</strong>
                                @if (auth('admin')->id() === $managedAdmin->id)
                                    <span class="admin-badge">You</span>
                                @endif
                            </td>
                            <td>{{ $managedAdmin->email }}</td>
                            <td>{{ $managedAdmin->created_at?->format('M j, Y') }}</td>
                            <td>
                                <div class="admin-actions">
                                    <x-admin.button
                                        href="{{ route('admin.admins.edit', $managedAdmin) }}"
                                        variant="warning"
                                        icon="lucide:pencil"
                                        icon-width="1rem"
                                        icon-height="1rem"
                                    >
                                        Edit
                                    </x-admin.button>

                                    @can('delete', $managedAdmin)
                                        <form
                                            method="POST"
                                            action="{{ route('admin.admins.destroy', $managedAdmin) }}"
                                            onsubmit="return confirm('Delete this admin account?')"
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
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="admin-table-empty">No admins found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($admins->hasPages())
            <div class="admin-pagination">
                {{ $admins->links() }}
            </div>
        @endif
    </div>
@endsection
