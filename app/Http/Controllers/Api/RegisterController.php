<?php

namespace App\Http\Controllers\Api;

use App\Enums\Gender;
use App\Http\Requests\Api\RegisterRequest;
use App\Models\Referral;
use App\Models\ReferralCode;
use App\Models\User;
use App\Support\ProfilePhoto;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class RegisterController extends ApiController
{
    /**
     * Save the account details Google sign-in does not provide.
     *
     * Requires the Sanctum bearer token from sign-in and the API signature headers.
     * profile_photo is not part of the signature. Optional referral_code is another member's code.
     * The response referral_code is this account's own code.
     */
    public function store(RegisterRequest $request, ProfilePhoto $profilePhoto): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $photo = $request->hasFile('profile_photo')
            ? $profilePhoto->store($request->file('profile_photo'), $user)
            : null;

        try {
            $referralCode = DB::transaction(function () use ($request, $user, $photo): ReferralCode {
                $user->fill([
                    'name' => $request->validated('name'),
                    'phone' => $request->validated('phone'),
                    'birth_date' => $request->validated('birth_date'),
                    'gender' => $request->validated('gender'),
                ]);

                if ($photo !== null) {
                    $user->profile_photo_disk = $photo['disk'];
                    $user->profile_photo_path = $photo['path'];
                }

                $user->save();

                $ownedCode = ReferralCode::issueFor($user);
                $this->applyReferral($user, $request->validated('referral_code'));

                return $ownedCode;
            });
        } catch (UniqueConstraintViolationException) {
            return $this->error('The phone number has already been taken.', 422, [
                'phone' => ['The phone number has already been taken.'],
            ]);
        }

        $user->refresh();

        return $this->success(
            data: $this->profile($user, $referralCode->code),
            message: 'Registration completed.',
        );
    }

    private function applyReferral(User $user, mixed $code): void
    {
        if (! is_string($code) || $code === '') {
            return;
        }

        $referralCode = ReferralCode::query()->where('code', $code)->first();

        if ($referralCode === null || $referralCode->user_id === $user->id) {
            return;
        }

        Referral::query()->firstOrCreate(
            ['referred_user_id' => $user->id],
            [
                'referral_code_id' => $referralCode->id,
                'referrer_user_id' => $referralCode->user_id,
            ],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function profile(User $user, string $referralCode): array
    {
        $gender = $user->gender;

        return [
            /**
             * @example 770001
             */
            'id' => (int) $user->id,
            /**
             * @example Buyer
             */
            'name' => $user->name,
            /**
             * @example buyer@example.com
             */
            'email' => $user->email,
            /**
             * @example 6281234567890
             */
            'phone' => $user->phone,
            /**
             * @example 1994-08-17
             */
            'birth_date' => $user->birth_date?->toDateString(),
            /**
             * @example female
             */
            'gender' => $gender instanceof Gender ? $gender->value : null,
            /**
             * @example http://localhost/storage/profile-photos/770001/photo.webp
             */
            'profile_photo_url' => $user->profile_photo_url,
            /**
             * @example K7MQ2P9
             */
            'referral_code' => $referralCode,
        ];
    }
}
