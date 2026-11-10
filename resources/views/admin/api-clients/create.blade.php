@extends('layouts.admin-app')

@section('title', 'Add API client')
@section('heading', 'Add API client')

@section('heading-icon')
    <x-icon name="lucide:key-round" width="1.35rem" height="1.35rem" />
@endsection

@section('content')
    <a href="{{ route('admin.api-clients.index') }}" class="admin-back-link">
        <x-icon name="lucide:arrow-left" width="1rem" height="1rem" />
        <span>Back to API clients</span>
    </a>

    @include('admin.api-clients._secret-warning')

    <div class="admin-panel admin-form">
        <div class="admin-panel-body">
            <form method="POST" action="{{ route('admin.api-clients.store') }}">
                @include('admin.api-clients._form')

                <div class="admin-form-actions">
                    <x-admin.button type="submit" icon="lucide:check">
                        Create API client
                    </x-admin.button>

                    <a href="{{ route('admin.api-clients.index') }}" class="admin-cancel-link">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
