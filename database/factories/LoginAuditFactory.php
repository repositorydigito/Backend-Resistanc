<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LoginAudit>
 */
class LoginAuditFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => $this->faker->boolean(90) ? User::factory() : null, // 90% have user_id
            'ip' => $this->faker->ipv4(),
            'user_agent' => $this->generateUserAgent(),
            'success' => $this->faker->boolean(85), // 85% successful logins
            'created_at' => $this->faker->dateTimeBetween('-6 months', 'now'),
        ];
    }

    /**
     * Create a successful login audit.
     */
    public function successful(): static
    {
        return $this->state([
            'success' => true,
            'user_id' => User::factory(), // Successful logins always have user_id
        ]);
    }

    /**
     * Create a failed login audit.
     */
    public function failed(): static
    {
        return $this->state([
            'success' => false,
            'user_id' => $this->faker->boolean(30) ? User::factory() : null, // Some failed attempts have user_id
        ]);
    }

    /**
     * Create a recent login audit (last 24 hours).
     */
    public function recent(): static
    {
        return $this->state([
            'created_at' => $this->faker->dateTimeBetween('-24 hours', 'now'),
        ]);
    }

    /**
     * Create a login audit from mobile device.
     */
    public function mobile(): static
    {
        return $this->state([
            'user_agent' => $this->generateMobileUserAgent(),
        ]);
    }

    /**
     * Create a login audit from desktop.
     */
    public function desktop(): static
    {
        return $this->state([
            'user_agent' => $this->generateDesktopUserAgent(),
        ]);
    }

    /**
     * Create a suspicious login audit (multiple failed attempts).
     */
    public function suspicious(): static
    {
        return $this->state([
            'success' => false,
            'user_id' => null,
            'ip' => $this->faker->randomElement([
                '192.168.1.100', '10.0.0.50', '172.16.0.25' // Common suspicious IPs
            ]),
        ]);
    }

    /**
     * Generate a realistic user agent string.
     */
    private function generateUserAgent(): string
    {
        $userAgents = [
            // Chrome
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            // Firefox
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:121.0) Gecko/20100101 Firefox/121.0',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:121.0) Gecko/20100101 Firefox/121.0',
            // Safari
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.1 Safari/605.1.15',
            // Mobile
            'Mozilla/5.0 (iPhone; CPU iPhone OS 17_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.1 Mobile/15E148 Safari/604.1',
            'Mozilla/5.0 (Linux; Android 14; SM-G998B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36',
        ];

        return $this->faker->randomElement($userAgents);
    }

    /**
     * Generate a mobile user agent.
     */
    private function generateMobileUserAgent(): string
    {
        $mobileUserAgents = [
            'Mozilla/5.0 (iPhone; CPU iPhone OS 17_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.1 Mobile/15E148 Safari/604.1',
            'Mozilla/5.0 (Linux; Android 14; SM-G998B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36',
            'Mozilla/5.0 (Linux; Android 13; Pixel 7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36',
        ];

        return $this->faker->randomElement($mobileUserAgents);
    }

    /**
     * Generate a desktop user agent.
     */
    private function generateDesktopUserAgent(): string
    {
        $desktopUserAgents = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:121.0) Gecko/20100101 Firefox/121.0',
        ];

        return $this->faker->randomElement($desktopUserAgents);
    }
}
