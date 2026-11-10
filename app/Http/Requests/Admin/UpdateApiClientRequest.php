<?php

namespace App\Http\Requests\Admin;

use App\Models\ApiClient;
use Illuminate\Foundation\Http\FormRequest;

class UpdateApiClientRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $apiClient = $this->route('api_client');

        return $apiClient instanceof ApiClient
            && ($this->user('admin')?->can('update', $apiClient) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
        ];
    }
}
