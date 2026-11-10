<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@yield('title', 'Admin') — {{ config('app.name', 'Serba Jaya') }}</title>

        @fonts

        <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
        <script src="https://code.iconify.design/iconify-icon/2.3.0/iconify-icon.min.js"></script>
        <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.9/dist/cdn.min.js"></script>
    </head>
    <body
        class="admin-body"
        x-data="{ sidebarOpen: false }"
        :class="{ 'is-locked': sidebarOpen }"
        @keydown.escape.window="sidebarOpen = false"
    >
        <div class="admin-shell">
            <button
                type="button"
                class="admin-backdrop"
                x-show="sidebarOpen"
                x-transition.opacity
                @click="sidebarOpen = false"
                aria-label="Close menu"
                style="display: none;"
            ></button>

            <aside
                class="admin-sidebar"
                :class="{ 'is-open': sidebarOpen }"
            >
                <div class="admin-sidebar-brand">
                    <div class="admin-sidebar-brand-main">
                        <span class="admin-sidebar-brand-icon">
                            <x-icon name="lucide:store" width="1.25rem" height="1.25rem" />
                        </span>
                        <div>
                            <div class="admin-sidebar-brand-name">{{ config('app.name', 'Serba Jaya') }}</div>
                            <div class="admin-sidebar-brand-meta">Admin panel</div>
                        </div>
                    </div>

                    <button
                        type="button"
                        class="admin-sidebar-close"
                        @click="sidebarOpen = false"
                    >
                        <span class="sr-only">Close menu</span>
                        <x-icon name="lucide:x" />
                    </button>
                </div>

                <nav class="admin-sidebar-nav">
                    <a
                        href="{{ route('admin.dashboard') }}"
                        @click="sidebarOpen = false"
                        @class([
                            'admin-nav-link',
                            'is-active' => request()->routeIs('admin.dashboard'),
                        ])
                    >
                        <x-icon name="lucide:layout-dashboard" width="1rem" height="1rem" />
                        <span>Dashboard</span>
                    </a>

                    <a
                        href="{{ route('admin.admins.index') }}"
                        @click="sidebarOpen = false"
                        @class([
                            'admin-nav-link',
                            'is-active' => request()->routeIs('admin.admins.*'),
                        ])
                    >
                        <x-icon name="lucide:shield-user" width="1rem" height="1rem" />
                        <span>Admins</span>
                    </a>

                    <a
                        href="{{ route('admin.api-clients.index') }}"
                        @click="sidebarOpen = false"
                        @class([
                            'admin-nav-link',
                            'is-active' => request()->routeIs('admin.api-clients.*'),
                        ])
                    >
                        <x-icon name="lucide:key-round" width="1rem" height="1rem" />
                        <span>API clients</span>
                    </a>
                </nav>

                <div class="admin-sidebar-footer">
                    <div class="admin-sidebar-user-copy" title="{{ auth('admin')->user()->email }}">
                        <strong>{{ auth('admin')->user()->name }}</strong>
                        <span class="admin-sidebar-user-email">{{ auth('admin')->user()->email }}</span>
                    </div>

                    <form method="POST" action="{{ route('admin.logout') }}" class="admin-sidebar-logout">
                        @csrf
                        <x-admin.button
                            type="submit"
                            variant="danger"
                            icon="lucide:log-out"
                            title="Log out"
                            aria-label="Log out"
                        />
                    </form>
                </div>
            </aside>

            <div class="admin-main">
                <header class="admin-page-header">
                    <button
                        type="button"
                        class="admin-menu-toggle"
                        @click="sidebarOpen = true"
                    >
                        <span class="sr-only">Open menu</span>
                        <x-icon name="lucide:menu" />
                    </button>

                    @hasSection('heading-icon')
                        <span class="admin-page-header-icon">
                            @yield('heading-icon')
                        </span>
                    @endif

                    <h1>@yield('heading', 'Dashboard')</h1>
                </header>

                <main class="admin-content">
                    <x-admin.flash />

                    @yield('content')
                </main>
            </div>
        </div>
    </body>
</html>
