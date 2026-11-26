<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class CheckReferralCodeRequest extends FormRequest
{
    /**
     * The route requires a Sanctum token. The API signature already authorized the app.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $code = $this->input('code');

        if (is_string($code)) {
            $this->merge([
                'code' => strtoupper(preg_replace('/[\s-]+/', '', $code) ?? ''),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:32'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.required' => 'The referral code is required.',
        ];
    }
}
