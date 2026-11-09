<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAdminRequest;
use App\Http\Requests\Admin\UpdateAdminRequest;
use App\Models\Admin;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AdminController extends Controller
{
    /**
     * Display a listing of admins.
     */
    public function index(): View
    {
        $this->authorize('viewAny', Admin::class);

        $admins = Admin::query()
            ->latest()
            ->paginate(10);

        return view('admin.admins.index', [
            'admins' => $admins,
        ]);
    }

    /**
     * Show the form for creating a new admin.
     */
    public function create(): View
    {
        $this->authorize('create', Admin::class);

        return view('admin.admins.create');
    }

    /**
     * Store a newly created admin.
     */
    public function store(StoreAdminRequest $request): RedirectResponse
    {
        Admin::query()->create($request->safe()->only(['name', 'email', 'password']));

        return redirect()
            ->route('admin.admins.index')
            ->with('success', 'Admin created successfully.');
    }

    /**
     * Show the form for editing the specified admin.
     */
    public function edit(Admin $admin): View
    {
        $this->authorize('update', $admin);

        return view('admin.admins.edit', [
            'admin' => $admin,
        ]);
    }

    /**
     * Update the specified admin.
     */
    public function update(UpdateAdminRequest $request, Admin $admin): RedirectResponse
    {
        $data = $request->safe()->only(['name', 'email']);

        if ($request->filled('password')) {
            $data['password'] = $request->string('password')->toString();
        }

        $admin->update($data);

        return redirect()
            ->route('admin.admins.index')
            ->with('success', 'Admin updated successfully.');
    }

    /**
     * Remove the specified admin.
     */
    public function destroy(Admin $admin): RedirectResponse
    {
        $this->authorize('delete', $admin);

        $admin->delete();

        return redirect()
            ->route('admin.admins.index')
            ->with('success', 'Admin deleted successfully.');
    }
}
