<?php

namespace App\Models;

use Database\Factories\ReferralFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['referral_code_id', 'referrer_user_id', 'referred_user_id'])]
class Referral extends Model
{
    /** @use HasFactory<ReferralFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<ReferralCode, $this>
     */
    public function referralCode(): BelongsTo
    {
        return $this->belongsTo(ReferralCode::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referrer_user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function referred(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_user_id');
    }
}
