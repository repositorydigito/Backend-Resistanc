<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AuthProvider;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SocialAccount>
 */
class SocialAccountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $provider = $this->faker->randomElement(AuthProvider::cases());

        return [
            'user_id' => User::factory(),
            'provider' => $provider,
            'provider_uid' => $this->generateProviderUid($provider),
            'provider_email' => $this->faker->unique()->safeEmail(),
            'token' => $this->generateToken(),
            'token_expires_at' => $this->faker->boolean(70)
                ? $this->faker->dateTimeBetween('now', '+1 year')
                : null,
        ];
    }

    /**
     * Create a Google social account.
     */
    public function google(): static
    {
        return $this->state([
            'provider' => AuthProvider::GOOGLE,
            'provider_uid' => $this->generateProviderUid(AuthProvider::GOOGLE),
            'provider_email' => $this->faker->unique()->safeEmail(),
        ]);
    }

    /**
     * Create a Facebook social account.
     */
    public function facebook(): static
    {
        return $this->state([
            'provider' => AuthProvider::FACEBOOK,
            'provider_uid' => $this->generateProviderUid(AuthProvider::FACEBOOK),
            'provider_email' => $this->faker->unique()->safeEmail(),
        ]);
    }

    /**
     * Create an account with expired token.
     */
    public function expiredToken(): static
    {
        return $this->state([
            'token_expires_at' => $this->faker->dateTimeBetween('-1 year', '-1 day'),
        ]);
    }

    /**
     * Create an account with long-lived token.
     */
    public function longLivedToken(): static
    {
        return $this->state([
            'token_expires_at' => null, // Never expires
        ]);
    }

    /**
     * Generate a realistic provider UID based on provider.
     */
    private function generateProviderUid(AuthProvider $provider): string
    {
        return match ($provider) {
            AuthProvider::GOOGLE => Str::random(20), // 20 characters
            AuthProvider::FACEBOOK => Str::random(16), // 16 characters
        };
    }

    /**
     * Generate a realistic OAuth token.
     */
    private function generateToken(): string
    {
        return 'ya29.' . Str::random(100) . '_' . Str::random(50);
    }
}
