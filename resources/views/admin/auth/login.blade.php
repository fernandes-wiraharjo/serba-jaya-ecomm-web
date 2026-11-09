@extends('layouts.admin')

@section('title', 'Admin login')

@section('body')
    <div class="shell flex min-h-screen items-center justify-center px-4 py-10">
        <div class="panel w-full max-w-md border border-slate-200 bg-white p-8 shadow-sm">
            <div class="brand mb-3 text-xs font-semibold tracking-[0.18em] text-teal-700 uppercase">
                {{ config('app.name', 'Serba Jaya') }}
            </div>

            <h1 class="mb-2 text-3xl font-semibold tracking-tight text-slate-900">Admin login</h1>
            <p class="lead mb-8 text-sm leading-6 text-slate-500">
                Sign in with your admin credentials to manage the store.
            </p>

            <form method="POST" action="{{ route('admin.login.store') }}" class="space-y-4">
                @csrf

                <div class="field">
                    <label for="email" class="mb-1.5 block text-sm font-medium text-slate-700">Email</label>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        autocomplete="username"
                        class="w-full border border-slate-300 px-3.5 py-2.5 text-slate-900 outline-none focus:border-teal-600 focus:ring-2 focus:ring-teal-600/20"
                    >
                    @error('email')
                        <p class="error mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="field">
                    <label for="password" class="mb-1.5 block text-sm font-medium text-slate-700">Password</label>
                    <input
                        id="password"
                        type="password"
                        name="password"
                        required
                        autocomplete="current-password"
                        class="w-full border border-slate-300 px-3.5 py-2.5 text-slate-900 outline-none focus:border-teal-600 focus:ring-2 focus:ring-teal-600/20"
                    >
                    @error('password')
                        <p class="error mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <label class="remember flex items-center gap-2 text-sm text-slate-600">
                    <input id="remember" type="checkbox" name="remember" class="border-slate-300 text-teal-700 focus:ring-teal-600">
                    Remember me
                </label>

                <button
                    type="submit"
                    class="w-full bg-teal-700 px-4 py-2.5 font-semibold text-white transition hover:bg-teal-800"
                >
                    Sign in
                </button>
            </form>
        </div>
    </div>
@endsection
