<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@yield('title', 'Admin') — {{ config('app.name', 'Serba Jaya') }}</title>

        @fonts

        <script src="https://code.iconify.design/iconify-icon/2.3.0/iconify-icon.min.js"></script>

        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @else
            <style>
                :root {
                    color-scheme: light;
                    --bg: #f4f6f8;
                    --surface: #ffffff;
                    --ink: #15202b;
                    --muted: #5b6b79;
                    --line: #d7dee5;
                    --accent: #0f766e;
                    --accent-ink: #ffffff;
                    --sidebar: #102a2a;
                    --sidebar-muted: #9bb5b3;
                }

                * { box-sizing: border-box; }

                body {
                    margin: 0;
                    min-height: 100vh;
                    font-family: "Instrument Sans", ui-sans-serif, system-ui, sans-serif;
                    background: var(--bg);
                    color: var(--ink);
                }

                a { color: inherit; text-decoration: none; }

                .app-shell {
                    min-height: 100vh;
                    display: grid;
                    grid-template-columns: 16rem 1fr;
                }

                .sidebar {
                    display: flex;
                    flex-direction: column;
                    background: var(--sidebar);
                    color: #fff;
                    border-right: 1px solid #0b1f1f;
                }

                .sidebar-brand {
                    display: flex;
                    align-items: center;
                    gap: 0.75rem;
                    padding: 1.25rem 1.25rem 1rem;
                    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
                }

                .sidebar-brand-icon {
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    width: 2.25rem;
                    height: 2.25rem;
                    background: rgba(94, 234, 212, 0.12);
                    color: #5eead4;
                }

                .sidebar-brand-name {
                    font-size: 0.75rem;
                    font-weight: 600;
                    letter-spacing: 0.18em;
                    text-transform: uppercase;
                    color: #5eead4;
                }

                .sidebar-brand-meta {
                    margin-top: 0.35rem;
                    font-size: 0.875rem;
                    color: var(--sidebar-muted);
                }

                .sidebar-nav {
                    display: flex;
                    flex-direction: column;
                    gap: 0.25rem;
                    padding: 1rem 0.75rem;
                    flex: 1;
                }

                .sidebar-link {
                    display: flex;
                    align-items: center;
                    gap: 0.7rem;
                    padding: 0.7rem 0.85rem;
                    color: #d7e7e5;
                    font-size: 0.925rem;
                    font-weight: 500;
                }

                .sidebar-link:hover,
                .sidebar-link.is-active {
                    background: rgba(94, 234, 212, 0.12);
                    color: #fff;
                }

                .sidebar-footer {
                    padding: 1rem 0.75rem 1.25rem;
                    border-top: 1px solid rgba(255, 255, 255, 0.08);
                }

                .sidebar-user {
                    display: flex;
                    align-items: flex-start;
                    gap: 0.7rem;
                    padding: 0 0.85rem 0.85rem;
                    font-size: 0.85rem;
                    color: var(--sidebar-muted);
                }

                .sidebar-user-icon {
                    margin-top: 0.1rem;
                    color: #5eead4;
                }

                .sidebar-user strong {
                    display: block;
                    color: #fff;
                    font-weight: 600;
                    margin-bottom: 0.15rem;
                }

                .ghost-button {
                    width: 100%;
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    gap: 0.55rem;
                    border: 1px solid rgba(255, 255, 255, 0.18);
                    border-radius: 0;
                    background: transparent;
                    color: #fff;
                    padding: 0.65rem 0.85rem;
                    font-weight: 500;
                    cursor: pointer;
                }

                .ghost-button:hover {
                    background: rgba(255, 255, 255, 0.06);
                }

                .main {
                    display: flex;
                    flex-direction: column;
                    min-width: 0;
                }

                .page-header {
                    display: flex;
                    align-items: center;
                    gap: 0.65rem;
                    padding: 1.25rem 1.5rem;
                    background: var(--surface);
                    border-bottom: 1px solid var(--line);
                }

                .page-header h1 {
                    margin: 0;
                    font-size: 1.35rem;
                    font-weight: 600;
                }

                .content {
                    padding: 1.5rem;
                }

                .card {
                    background: var(--surface);
                    border: 1px solid var(--line);
                    padding: 1.5rem;
                }
            </style>
        @endif
    </head>
    <body class="min-h-screen bg-slate-100 text-slate-900 antialiased">
        <div class="app-shell min-h-screen lg:grid lg:grid-cols-[16rem_1fr]">
            <aside class="sidebar flex min-h-screen flex-col border-r border-slate-900 bg-teal-950 text-white">
                <div class="sidebar-brand flex items-center gap-3 border-b border-white/10 px-5 py-5">
                    <span class="sidebar-brand-icon inline-flex h-9 w-9 items-center justify-center bg-teal-400/15 text-teal-300">
                        <x-icon name="lucide:store" width="1.25rem" height="1.25rem" />
                    </span>
                    <div>
                        <div class="sidebar-brand-name text-xs font-semibold tracking-[0.18em] text-teal-300 uppercase">
                            {{ config('app.name', 'Serba Jaya') }}
                        </div>
                        <div class="sidebar-brand-meta mt-1 text-sm text-teal-100/70">Admin panel</div>
                    </div>
                </div>

                <nav class="sidebar-nav flex flex-1 flex-col gap-1 px-3 py-4">
                    <a
                        href="{{ route('admin.dashboard') }}"
                        @class([
                            'sidebar-link flex items-center gap-2.5 px-3.5 py-2.5 text-sm font-medium text-teal-50/90 hover:bg-teal-800/60 hover:text-white',
                            'is-active bg-teal-800/60 text-white' => request()->routeIs('admin.dashboard'),
                        ])
                    >
                        <x-icon name="lucide:layout-dashboard" />
                        <span>Dashboard</span>
                    </a>
                </nav>

                <div class="sidebar-footer border-t border-white/10 px-3 py-4">
                    <div class="sidebar-user flex items-start gap-2.5 px-3.5 pb-3 text-sm text-teal-100/70">
                        <span class="sidebar-user-icon mt-0.5 text-teal-300">
                            <x-icon name="lucide:user-round" />
                        </span>
                        <div>
                            <strong class="mb-0.5 block font-semibold text-white">{{ auth('admin')->user()->name }}</strong>
                            {{ auth('admin')->user()->email }}
                        </div>
                    </div>

                    <form method="POST" action="{{ route('admin.logout') }}">
                        @csrf
                        <button
                            type="submit"
                            class="ghost-button inline-flex w-full items-center justify-center gap-2 border border-white/20 px-3.5 py-2 text-sm font-medium text-white transition hover:bg-white/10"
                        >
                            <x-icon name="lucide:log-out" />
                            <span>Log out</span>
                        </button>
                    </form>
                </div>
            </aside>

            <div class="main flex min-w-0 flex-col">
                <header class="page-header flex items-center gap-2.5 border-b border-slate-200 bg-white px-6 py-5">
                    @hasSection('heading-icon')
                        <span class="text-teal-700">
                            @yield('heading-icon')
                        </span>
                    @endif
                    <h1 class="text-xl font-semibold tracking-tight text-slate-900">@yield('heading', 'Dashboard')</h1>
                </header>

                <main class="content flex-1 p-6">
                    @yield('content')
                </main>
            </div>
        </div>
    </body>
</html>
