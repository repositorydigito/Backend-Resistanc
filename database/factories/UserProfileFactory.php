<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Gender;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserProfile>
 */
class UserProfileFactory extends Factory
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
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'birth_date' => $this->faker->dateTimeBetween('-65 years', '-16 years'),
            'gender' => $this->faker->randomElement(Gender::cases()),
            'shoe_size_eu' => $this->faker->numberBetween(35, 48),
        ];
    }

    /**
     * Create a female profile.
     */
    public function female(): static
    {
        return $this->state(fn (array $attributes) => [
            'first_name' => $this->faker->firstNameFemale(),
            'gender' => Gender::FEMALE,
        ]);
    }

    /**
     * Create a male profile.
     */
    public function male(): static
    {
        return $this->state(fn (array $attributes) => [
            'first_name' => $this->faker->firstNameMale(),
            'gender' => Gender::MALE,
        ]);
    }

    /**
     * Create a young adult profile (18-25 years).
     */
    public function youngAdult(): static
    {
        return $this->state(fn (array $attributes) => [
            'birth_date' => $this->faker->dateTimeBetween('-25 years', '-18 years'),
        ]);
    }

    /**
     * Create a senior profile (50+ years).
     */
    public function senior(): static
    {
        return $this->state(fn (array $attributes) => [
            'birth_date' => $this->faker->dateTimeBetween('-75 years', '-50 years'),
        ]);
    }
}
