@extends('layouts.admin-app')

@section('title', 'Dashboard')
@section('heading', 'Dashboard')

@section('heading-icon')
    <x-icon name="lucide:layout-dashboard" width="1.35rem" height="1.35rem" />
@endsection

@section('content')
    <div class="admin-panel">
        <div class="admin-panel-body">
            <h2 style="margin: 0 0 0.5rem; font-size: 1.35rem;">Welcome, {{ auth('admin')->user()->name }}</h2>
            <p style="margin: 0; color: var(--admin-muted); font-size: 0.9rem; line-height: 1.6;">
                Your admin account is ready. Product, order, and catalog modules can be added next.
            </p>
        </div>
    </div>
@endsection
