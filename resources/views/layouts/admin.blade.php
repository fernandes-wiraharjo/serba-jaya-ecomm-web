<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@yield('title', 'Admin') — {{ config('app.name', 'Serba Jaya') }}</title>

        @fonts

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
                    --danger: #b42318;
                }

                * { box-sizing: border-box; }

                body {
                    margin: 0;
                    min-height: 100vh;
                    font-family: "Instrument Sans", ui-sans-serif, system-ui, sans-serif;
                    background:
                        radial-gradient(circle at top left, rgba(15, 118, 110, 0.12), transparent 32%),
                        linear-gradient(180deg, #eef3f6 0%, var(--bg) 48%, #e8eef2 100%);
                    color: var(--ink);
                }

                a { color: inherit; text-decoration: none; }

                .shell {
                    min-height: 100vh;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    padding: 2rem 1rem;
                }

                .panel {
                    width: 100%;
                    max-width: 28rem;
                    background: var(--surface);
                    border: 1px solid var(--line);
                    padding: 2rem;
                    box-shadow: 0 18px 40px rgba(21, 32, 43, 0.08);
                }

                .brand {
                    font-size: 0.85rem;
                    font-weight: 600;
                    letter-spacing: 0.08em;
                    text-transform: uppercase;
                    color: var(--accent);
                    margin-bottom: 0.75rem;
                }

                h1 {
                    margin: 0 0 0.5rem;
                    font-size: 1.75rem;
                    line-height: 1.2;
                }

                .lead {
                    margin: 0 0 1.75rem;
                    color: var(--muted);
                    line-height: 1.5;
                }

                label {
                    display: block;
                    margin-bottom: 0.4rem;
                    font-size: 0.9rem;
                    font-weight: 500;
                }

                .field { margin-bottom: 1rem; }

                input[type="text"],
                input[type="email"],
                input[type="password"] {
                    width: 100%;
                    border: 1px solid var(--line);
                    border-radius: 0;
                    padding: 0.8rem 0.9rem;
                    background: #fff;
                    color: var(--ink);
                }

                input:focus {
                    outline: 2px solid rgba(15, 118, 110, 0.25);
                    border-color: var(--accent);
                }

                .error {
                    margin-top: 0.4rem;
                    color: var(--danger);
                    font-size: 0.85rem;
                }

                .remember {
                    display: flex;
                    align-items: center;
                    gap: 0.5rem;
                    margin: 1rem 0 1.25rem;
                    color: var(--muted);
                    font-size: 0.9rem;
                }

                button {
                    width: 100%;
                    border: 0;
                    border-radius: 0;
                    padding: 0.85rem 1rem;
                    background: var(--accent);
                    color: var(--accent-ink);
                    font-weight: 600;
                    cursor: pointer;
                }

                button:hover { filter: brightness(0.96); }

            </style>
        @endif
    </head>
    <body class="min-h-screen bg-slate-100 text-slate-900 antialiased">
        @yield('body')
    </body>
</html>
