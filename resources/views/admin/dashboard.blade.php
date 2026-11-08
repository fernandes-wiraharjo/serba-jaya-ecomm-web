@extends('layouts.admin-app')

@section('title', 'Dashboard')
@section('heading', 'Dashboard')

@section('heading-icon')
    <x-icon name="lucide:layout-dashboard" width="1.35rem" height="1.35rem" />
@endsection

@section('content')
    <div class="card border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-2xl font-semibold tracking-tight text-slate-900">Welcome, {{ auth('admin')->user()->name }}</h2>
        <p class="mt-2 text-sm leading-6 text-slate-500">
            Your admin account is ready. Product, order, and catalog modules can be added next.
        </p>
    </div>
@endsection
