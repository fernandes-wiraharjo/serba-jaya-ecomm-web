<?php

namespace App\Policies;

use App\Models\Admin;

class AdminPolicy
{
    /**
     * Determine whether the admin can view any models.
     */
    public function viewAny(Admin $admin): bool
    {
        return true;
    }

    /**
     * Determine whether the admin can create models.
     */
    public function create(Admin $admin): bool
    {
        return true;
    }

    /**
     * Determine whether the admin can update the model.
     */
    public function update(Admin $admin, Admin $model): bool
    {
        return true;
    }

    /**
     * Determine whether the admin can delete the model.
     */
    public function delete(Admin $admin, Admin $model): bool
    {
        if ($admin->is($model)) {
            return false;
        }

        return Admin::query()->count() > 1;
    }
}
