<?php

namespace App\Http\Middleware;

use App\Models\Admin;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class HandleAdminAccess
{
    /**
     * Handle an incoming admin request.
     *
     * Modes:
     * - setup: only available before the first admin exists
     * - unauthenticated: available after setup, but only when not logged in as admin
     * - authenticated: available after setup for logged-in admin routes
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $mode = 'authenticated'): Response
    {
        $adminExists = Admin::query()->exists();

        return match ($mode) {
            'setup' => $this->handleSetup($request, $next, $adminExists),
            'unauthenticated' => $this->handleUnauthenticated($request, $next, $adminExists),
            'authenticated' => $this->handleAuthenticated($request, $next, $adminExists),
            default => abort(500, "Invalid admin access mode [{$mode}]."),
        };
    }

    /**
     * @param  Closure(Request): (Response)  $next
     */
    private function handleSetup(Request $request, Closure $next, bool $adminExists): Response
    {
        if ($adminExists) {
            return redirect()->route('admin.login');
        }

        return $next($request);
    }

    /**
     * @param  Closure(Request): (Response)  $next
     */
    private function handleUnauthenticated(Request $request, Closure $next, bool $adminExists): Response
    {
        if (! $adminExists) {
            return redirect()->route('admin.setup');
        }

        if (Auth::guard('admin')->check()) {
            return redirect()->route('admin.dashboard');
        }

        return $next($request);
    }

    /**
     * @param  Closure(Request): (Response)  $next
     */
    private function handleAuthenticated(Request $request, Closure $next, bool $adminExists): Response
    {
        if (! $adminExists) {
            return redirect()->route('admin.setup');
        }

        return $next($request);
    }
}
