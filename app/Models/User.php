<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\Gender;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'phone', 'birth_date', 'gender', 'profile_photo_disk', 'profile_photo_path'])]
#[Hidden(['password', 'remember_token', 'profile_photo_disk', 'profile_photo_path'])]
#[Appends(['profile_photo_url'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'birth_date' => 'date',
            'gender' => Gender::class,
        ];
    }

    /**
     * Build the photo URL from the disk and path stored for this user.
     *
     * @return Attribute<string|null, never>
     */
    protected function profilePhotoUrl(): Attribute
    {
        return Attribute::get(function (): ?string {
            $disk = $this->profile_photo_disk;
            $path = $this->profile_photo_path;

            if (! is_string($disk) || $disk === '' || ! is_string($path) || $path === '') {
                return null;
            }

            if (! is_array(config('filesystems.disks.'.$disk))) {
                return null;
            }

            return Storage::disk($disk)->url($path);
        });
    }

    /**
     * @return HasOne<ReferralCode, $this>
     */
    public function referralCode(): HasOne
    {
        return $this->hasOne(ReferralCode::class);
    }

    /**
     * @return HasOne<Referral, $this>
     */
    public function referral(): HasOne
    {
        return $this->hasOne(Referral::class, 'referred_user_id');
    }

    /**
     * @return HasMany<SocialAccount, $this>
     */
    public function socialAccounts(): HasMany
    {
        return $this->hasMany(SocialAccount::class);
    }
}
