<?php

namespace Tests\Feature;

use App\Models\ApiClient;
use App\Models\Referral;
use App\Models\ReferralCode;
use App\Models\User;
use App\Support\ApiSignature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_update_profile_returns_401_when_the_user_is_not_signed_in(): void
    {
        $this->signedPost('/api/user/profile/update', [
            'name' => 'Buyer',
            'phone' => '081234567890',
        ])->assertUnauthorized()
            ->assertJsonPath('message', 'Unauthenticated.');
    }

    public function test_update_profile_returns_422_when_name_and_phone_are_missing(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->signedPost('/api/user/profile/update', [])
            ->assertUnprocessable()
            ->assertJsonPath('errors.name.0', 'The name is required.')
            ->assertJsonPath('errors.phone.0', 'The phone number is required.');
    }

    public function test_update_profile_returns_422_when_profile_fields_are_invalid(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->signedPost('/api/user/profile/update', [
            'name' => 'Buyer',
            'phone' => '0212345678',
            'birth_date' => '17-08-1994',
            'gender' => 'other',
        ])->assertUnprocessable()
            ->assertJsonPath('errors.phone.0', 'The phone number must be an Indonesian mobile number.')
            ->assertJsonPath('errors.birth_date.0', 'The birth date must use Y-m-d.')
            ->assertJsonPath('errors.gender.0', 'The selected gender is invalid.');
    }

    public function test_update_profile_normalizes_the_phone_and_stores_the_profile(): void
    {
        $user = User::factory()->create([
            'name' => 'Google Name',
            'email' => 'buyer@example.com',
        ]);
        Sanctum::actingAs($user);

        $response = $this->signedPost('/api/user/profile/update', [
            'name' => 'Buyer Name',
            'phone' => '0812-3456-7890',
            'birth_date' => '1994-08-17',
            'gender' => 'female',
        ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Profile updated.')
            ->assertJsonPath('data.email', 'buyer@example.com')
            ->assertJsonPath('data.name', 'Buyer Name')
            ->assertJsonPath('data.phone', '6281234567890')
            ->assertJsonPath('data.birth_date', '1994-08-17')
            ->assertJsonPath('data.gender', 'female')
            ->assertJsonPath('data.profile_photo_url', null)
            ->assertJsonPath('errors', null);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'phone' => '6281234567890',
            'gender' => 'female',
        ]);
        $this->assertDatabaseHas('referral_codes', [
            'user_id' => $user->id,
            'code' => $response->json('data.referral_code'),
        ]);
    }

    public function test_update_profile_returns_422_when_the_phone_is_already_used(): void
    {
        User::factory()->create(['phone' => '6281234567890']);
        Sanctum::actingAs(User::factory()->create());

        $this->signedPost('/api/user/profile/update', [
            'name' => 'Buyer',
            'phone' => '081234567890',
        ])->assertUnprocessable()
            ->assertJsonPath('errors.phone.0', 'The phone number has already been taken.');
    }

    public function test_update_profile_allows_the_same_user_to_keep_their_phone(): void
    {
        $user = User::factory()->create(['phone' => '6281234567890']);
        Sanctum::actingAs($user);

        $this->signedPost('/api/user/profile/update', [
            'name' => 'Updated',
            'phone' => '081234567890',
        ])->assertOk()
            ->assertJsonPath('data.name', 'Updated')
            ->assertJsonPath('data.phone', '6281234567890');

        $this->assertDatabaseCount('referral_codes', 1);
    }

    public function test_update_profile_returns_422_when_the_referral_code_does_not_exist(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->signedPost('/api/user/profile/update', [
            'name' => 'Buyer',
            'phone' => '081234567890',
            'referral_code' => 'MISSING1',
        ])->assertUnprocessable()
            ->assertJsonPath('errors.referral_code.0', 'The referral code is invalid.');

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('referrals', 0);
    }

    public function test_update_profile_stores_a_referral_and_issues_the_users_own_code(): void
    {
        $referrer = User::factory()->create();
        ReferralCode::factory()->create([
            'user_id' => $referrer->id,
            'code' => 'K7MQ2P9X',
        ]);
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->signedPost('/api/user/profile/update', [
            'name' => 'Buyer',
            'phone' => '+62 812 3456 7890',
            'referral_code' => 'k7mq-2p9x',
        ]);

        $response->assertOk();
        $ownCode = $response->json('data.referral_code');
        $this->assertIsString($ownCode);
        $this->assertNotSame('K7MQ2P9X', $ownCode);

        $this->assertDatabaseHas('referrals', [
            'referrer_user_id' => $referrer->id,
            'referred_user_id' => $user->id,
        ]);
        $this->assertSame(1, Referral::query()->count());
    }

    public function test_update_profile_returns_422_when_a_different_referral_was_already_applied(): void
    {
        $user = User::factory()->create();
        $applied = ReferralCode::factory()->create(['code' => 'FIRSTCOD']);
        ReferralCode::factory()->create(['code' => 'SECONDCD']);
        Referral::factory()->create([
            'referral_code_id' => $applied->id,
            'referrer_user_id' => $applied->user_id,
            'referred_user_id' => $user->id,
        ]);
        Sanctum::actingAs($user);

        $this->signedPost('/api/user/profile/update', [
            'name' => 'Buyer',
            'phone' => '081234567890',
            'referral_code' => 'SECONDCD',
        ])->assertUnprocessable()
            ->assertJsonPath('errors.referral_code.0', 'A referral code has already been applied.');
    }

    public function test_update_profile_returns_422_when_the_user_uses_their_own_referral_code(): void
    {
        $user = User::factory()->create();
        $code = ReferralCode::issueFor($user);
        Sanctum::actingAs($user);

        $this->signedPost('/api/user/profile/update', [
            'name' => 'Buyer',
            'phone' => '081234567890',
            'referral_code' => $code->code,
        ])->assertUnprocessable()
            ->assertJsonPath('errors.referral_code.0', 'The referral code is invalid.');
    }

    public function test_update_profile_stores_a_profile_photo_as_a_600_pixel_webp_url(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->signedPost('/api/user/profile/update', [
            'name' => 'Buyer',
            'phone' => '081234567890',
        ], [
            'profile_photo' => UploadedFile::fake()->image('avatar.png', 1200, 800),
        ]);

        $response->assertOk();

        $files = Storage::disk('public')->allFiles('profile-photos/'.$user->id);
        $this->assertCount(1, $files);
        $this->assertStringEndsWith('.webp', $files[0]);

        $stored = (new ImageManager(Driver::class))->decodeBinary(Storage::disk('public')->get($files[0]));
        $this->assertSame(600, $stored->width());
        $this->assertSame(600, $stored->height());
        $this->assertSame(Storage::disk('public')->url($files[0]), $response->json('data.profile_photo_url'));
        $this->assertSame('public', $user->fresh()->profile_photo_disk);
        $this->assertSame($files[0], $user->fresh()->profile_photo_path);

        $second = $this->signedPost('/api/user/profile/update', [
            'name' => 'Buyer',
            'phone' => '081234567890',
        ], [
            'profile_photo' => UploadedFile::fake()->image('replacement.png', 900, 900),
        ]);

        $second->assertOk();
        Storage::disk('public')->assertMissing($files[0]);
        $remaining = Storage::disk('public')->allFiles('profile-photos/'.$user->id);
        $this->assertCount(1, $remaining);
        $this->assertSame(Storage::disk('public')->url($remaining[0]), $second->json('data.profile_photo_url'));
    }

    public function test_update_profile_deletes_the_previous_photo_from_its_stored_disk(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        $user = User::factory()->create([
            'profile_photo_disk' => 'local',
            'profile_photo_path' => 'profile-photos/old.webp',
        ]);
        Storage::disk('local')->put('profile-photos/old.webp', 'old');
        Sanctum::actingAs($user);

        $response = $this->signedPost('/api/user/profile/update', [
            'name' => 'Buyer',
            'phone' => '081234567890',
        ], [
            'profile_photo' => UploadedFile::fake()->image('avatar.png', 800, 800),
        ]);

        $response->assertOk();
        Storage::disk('local')->assertMissing('profile-photos/old.webp');
        $this->assertSame('public', $user->fresh()->profile_photo_disk);
        $this->assertSame(
            Storage::disk('public')->url($user->fresh()->profile_photo_path),
            $response->json('data.profile_photo_url'),
        );
    }

    public function test_referral_code_check_returns_whether_the_code_can_be_used(): void
    {
        $referrer = User::factory()->create();
        ReferralCode::factory()->create([
            'user_id' => $referrer->id,
            'code' => 'K7MQ2P9X',
        ]);
        $user = User::factory()->create();
        $ownCode = ReferralCode::issueFor($user);
        Sanctum::actingAs($user);

        $this->signedPost('/api/user/referral/code-check', [
            'code' => 'k7mq 2p9x',
        ])->assertOk()
            ->assertJsonPath('data.valid', true)
            ->assertJsonPath('message', 'Referral code is valid.');

        $this->signedPost('/api/user/referral/code-check', [
            'code' => $ownCode->code,
        ])->assertOk()
            ->assertJsonPath('data.valid', false)
            ->assertJsonPath('message', 'Referral code is invalid.');

        $this->signedPost('/api/user/referral/code-check', [
            'code' => 'MISSING1',
        ])->assertOk()
            ->assertJsonPath('data.valid', false);

        $this->signedPost('/api/user/referral/code-check', [])
            ->assertUnprocessable()
            ->assertJsonPath('errors.code.0', 'The referral code is required.');
    }

    public function test_referral_code_check_returns_401_when_the_user_is_not_signed_in(): void
    {
        $this->signedPost('/api/user/referral/code-check', [
            'code' => 'K7MQ2P9X',
        ])->assertUnauthorized()
            ->assertJsonPath('message', 'Unauthenticated.');
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, UploadedFile>  $files
     */
    private function signedPost(string $uri, array $payload, array $files = []): TestResponse
    {
        $client = ApiClient::factory()->create();
        $nonce = (string) Str::uuid();
        $signature = app(ApiSignature::class)->hash($payload, $nonce, $client->secret);
        $headers = [
            'X-Client-ID' => $client->client_id,
            'X-Nonce' => $nonce,
            'X-Api-Signature' => $signature,
            'Accept' => 'application/json',
        ];

        if ($files === []) {
            return $this->json('POST', $uri, $payload, $headers);
        }

        return $this->post($uri, array_merge($payload, $files), $headers);
    }
}
