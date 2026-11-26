<?php

namespace App\Http\Requests\Api;

use App\Enums\Gender;
use App\Models\Referral;
use App\Models\ReferralCode;
use App\Support\IndonesianPhone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class RegisterRequest extends FormRequest
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
        $merge = [];
        $phone = $this->input('phone');

        if (is_string($phone)) {
            $merge['phone'] = IndonesianPhone::normalize($phone);
        }

        foreach (['birth_date', 'gender'] as $field) {
            if ($this->input($field) === '') {
                $merge[$field] = null;
            }
        }

        $referralCode = $this->input('referral_code');

        if ($referralCode === '') {
            $merge['referral_code'] = null;
        } elseif (is_string($referralCode)) {
            $merge['referral_code'] = strtoupper(preg_replace('/[\s-]+/', '', $referralCode) ?? '');
        }

        if ($merge !== []) {
            $this->merge($merge);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => [
                'required',
                'string',
                'regex:/^628\d{8,12}$/',
                Rule::unique('users', 'phone')->ignore($this->user()?->id),
            ],
            'birth_date' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today', 'after:1900-01-01'],
            'gender' => ['nullable', Rule::enum(Gender::class)],
            'profile_photo' => ['nullable', 'image', 'max:5120'],
            'referral_code' => ['nullable', 'string', 'max:32'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The name is required.',
            'phone.required' => 'The phone number is required.',
            'phone.regex' => 'The phone number must be an Indonesian mobile number.',
            'phone.unique' => 'The phone number has already been taken.',
            'birth_date.date_format' => 'The birth date must use Y-m-d.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $code = $this->input('referral_code');

            if (! is_string($code) || $code === '') {
                return;
            }

            $referralCode = ReferralCode::query()->where('code', $code)->first();
            $user = $this->user();

            if ($referralCode === null || $referralCode->user_id === $user?->id) {
                $validator->errors()->add('referral_code', 'The referral code is invalid.');

                return;
            }

            $existing = Referral::query()->where('referred_user_id', $user?->id)->first();

            if ($existing !== null && $existing->referral_code_id !== $referralCode->id) {
                $validator->errors()->add('referral_code', 'A referral code has already been applied.');
            }
        });
    }
}
