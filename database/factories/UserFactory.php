<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the model has two-factor authentication configured.
     */
    public function withTwoFactor(): static
    {
        return $this->state(fn (array $attributes) => [
            'two_factor_secret' => encrypt('secret'),
            'two_factor_recovery_codes' => encrypt(json_encode(['recovery-code-1'])),
            'two_factor_confirmed_at' => now(),
        ]);
    }

    /**
     * Create an empty creator profile.
     */
    public function creator(): static
    {
        return $this->afterCreating(function (User $user): void {
            $user->creatorProfile()->firstOrCreate(
                ['user_id' => $user->id],
                ['country' => 'FR'],
            );
        });
    }

    /**
     * Create an empty company profile.
     */
    public function company(): static
    {
        return $this->afterCreating(function (User $user): void {
            $user->company()->firstOrCreate(
                ['user_id' => $user->id],
                ['website' => 'https://example.com'],
            );
        });
    }

    /**
     * Mark owned profiles as onboarded.
     */
    public function onboarded(): static
    {
        return $this->afterCreating(function (User $user): void {
            if ($user->creatorProfile !== null) {
                $user->creatorProfile->update(['onboarded_at' => now()]);
            }

            if ($user->company !== null) {
                $user->company->update(['onboarded_at' => now()]);
            }
        });
    }
}
