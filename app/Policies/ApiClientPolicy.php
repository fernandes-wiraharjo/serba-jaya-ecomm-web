<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\ApiClient;

class ApiClientPolicy
{
    /**
     * Determine whether the admin can view any API clients.
     */
    public function viewAny(Admin $admin): bool
    {
        return true;
    }

    /**
     * Determine whether the admin can create API clients.
     */
    public function create(Admin $admin): bool
    {
        return true;
    }

    /**
     * Determine whether the admin can update the API client.
     */
    public function update(Admin $admin, ApiClient $apiClient): bool
    {
        return true;
    }

    /**
     * Determine whether the admin can delete the API client.
     */
    public function delete(Admin $admin, ApiClient $apiClient): bool
    {
        return true;
    }
}
