<?php

namespace Database\Factories;

use App\Models\ReferralCode;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReferralCode>
 */
class ReferralCodeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'code' => strtoupper(fake()->unique()->bothify('****####')),
        ];
    }
}
