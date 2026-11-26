<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\CheckReferralCodeRequest;
use App\Models\ReferralCode;
use Illuminate\Http\JsonResponse;

class ReferralCodeController extends ApiController
{
    /**
     * Report whether a referral code can be used by the signed-in user.
     *
     * The signed-in user's own code is not valid. A valid result can enable the registration submit button.
     */
    public function check(CheckReferralCodeRequest $request): JsonResponse
    {
        $code = ReferralCode::query()->where('code', $request->validated('code'))->first();
        $valid = $code !== null && $code->user_id !== $request->user()?->id;

        return $this->success(
            data: [
                /**
                 * @example true
                 */
                'valid' => $valid,
            ],
            message: $valid ? 'Referral code is valid.' : 'Referral code is invalid.',
        );
    }
}
