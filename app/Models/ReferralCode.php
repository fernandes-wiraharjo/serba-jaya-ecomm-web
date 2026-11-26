<?php

namespace App\Models;

use Database\Factories\ReferralCodeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['user_id', 'code'])]
class ReferralCode extends Model
{
    /** @use HasFactory<ReferralCodeFactory> */
    use HasFactory;

    /**
     * Return the user's share code, creating one when registration has not issued it yet.
     */
    public static function issueFor(User $user): self
    {
        $existing = self::query()->where('user_id', $user->id)->first();

        if ($existing !== null) {
            return $existing;
        }

        do {
            $code = Str::upper(Str::random(7));
        } while (self::query()->where('code', $code)->exists());

        return self::query()->create([
            'user_id' => $user->id,
            'code' => $code,
        ]);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<Referral, $this>
     */
    public function referrals(): HasMany
    {
        return $this->hasMany(Referral::class);
    }
}
