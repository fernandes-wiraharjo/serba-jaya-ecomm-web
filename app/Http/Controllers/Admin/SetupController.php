<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSetupRequest;
use App\Models\Admin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SetupController extends Controller
{
    /**
     * Show the first-time admin setup form.
     */
    public function create(): View
    {
        return view('admin.setup');
    }

    /**
     * Create the initial admin account.
     */
    public function store(StoreSetupRequest $request): RedirectResponse
    {
        $admin = Admin::query()->create($request->safe()->only(['name', 'email', 'password']));

        Auth::guard('admin')->login($admin);

        $request->session()->regenerate();

        return redirect()->route('admin.dashboard');
    }
}
