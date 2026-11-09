@extends('layouts.admin-app')

@section('title', 'Edit admin')
@section('heading', 'Edit admin')

@section('heading-icon')
    <x-icon name="lucide:user-pen" width="1.35rem" height="1.35rem" />
@endsection

@section('content')
    <a href="{{ route('admin.admins.index') }}" class="admin-back-link">
        <x-icon name="lucide:arrow-left" width="1rem" height="1rem" />
        <span>Back to admins</span>
    </a>

    <div class="admin-panel admin-form">
        <div class="admin-panel-body">
            <form method="POST" action="{{ route('admin.admins.update', $admin) }}">
                @method('PUT')
                @include('admin.admins._form', ['admin' => $admin])

                <div class="admin-form-actions">
                    <x-admin.button type="submit" icon="lucide:check">
                        Save changes
                    </x-admin.button>

                    <a href="{{ route('admin.admins.index') }}" class="admin-cancel-link">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
