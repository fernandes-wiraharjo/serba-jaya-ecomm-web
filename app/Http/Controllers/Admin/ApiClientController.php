<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreApiClientRequest;
use App\Http\Requests\Admin\UpdateApiClientRequest;
use App\Models\ApiClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ApiClientController extends Controller
{
    /**
     * Display the API clients.
     */
    public function index(): View
    {
        $this->authorize('viewAny', ApiClient::class);

        $apiClients = ApiClient::query()
            ->latest()
            ->paginate(10);

        return view('admin.api-clients.index', [
            'apiClients' => $apiClients,
        ]);
    }

    /**
     * Show the form for creating an API client.
     */
    public function create(): View
    {
        $this->authorize('create', ApiClient::class);

        return view('admin.api-clients.create');
    }

    /**
     * Store a newly created API client.
     */
    public function store(StoreApiClientRequest $request): RedirectResponse
    {
        $secret = ApiClient::newSecret();

        $apiClient = ApiClient::query()->create([
            'name' => $request->validated('name'),
            'client_id' => ApiClient::newClientId(),
            'secret' => $secret,
        ]);

        return redirect()
            ->route('admin.api-clients.index')
            ->with('success', 'API client created. Copy the secret now. It will not be shown again.')
            ->with('api_client_credentials', [
                'client_id' => $apiClient->client_id,
                'secret' => $secret,
            ]);
    }

    /**
     * Show the form for editing the API client.
     */
    public function edit(ApiClient $apiClient): View
    {
        $this->authorize('update', $apiClient);

        return view('admin.api-clients.edit', [
            'apiClient' => $apiClient,
        ]);
    }

    /**
     * Update the API client name.
     */
    public function update(UpdateApiClientRequest $request, ApiClient $apiClient): RedirectResponse
    {
        $apiClient->update([
            'name' => $request->validated('name'),
        ]);

        return redirect()
            ->route('admin.api-clients.index')
            ->with('success', 'API client updated.');
    }

    /**
     * Replace the API client secret.
     */
    public function regenerate(ApiClient $apiClient): RedirectResponse
    {
        $this->authorize('update', $apiClient);

        $secret = ApiClient::newSecret();

        $apiClient->update([
            'secret' => $secret,
        ]);

        return redirect()
            ->route('admin.api-clients.index')
            ->with('success', 'Secret regenerated. Copy it now. It will not be shown again.')
            ->with('api_client_credentials', [
                'client_id' => $apiClient->client_id,
                'secret' => $secret,
            ]);
    }

    /**
     * Delete the API client.
     */
    public function destroy(ApiClient $apiClient): RedirectResponse
    {
        $this->authorize('delete', $apiClient);

        $apiClient->delete();

        return redirect()
            ->route('admin.api-clients.index')
            ->with('success', 'API client deleted.');
    }
}
