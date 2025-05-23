<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserContact>
 */
class UserContactFactory extends Factory
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
            'phone' => $this->faker->unique()->numerify('9########'), // Unique 9-digit mobile number
            'address_line' => $this->faker->streetAddress(),
            'city' => $this->faker->randomElement([
                'Lima', 'Arequipa', 'Trujillo', 'Chiclayo', 'Piura',
                'Iquitos', 'Cusco', 'Huancayo', 'Chimbote', 'Tacna'
            ]),
            'country' => 'PE',
            'is_primary' => $this->faker->boolean(70), // 70% chance of being primary
        ];
    }

    /**
     * Create a primary contact.
     */
    public function primary(): static
    {
        return $this->state([
            'is_primary' => true,
        ]);
    }

    /**
     * Create a secondary contact.
     */
    public function secondary(): static
    {
        return $this->state([
            'is_primary' => false,
        ]);
    }

    /**
     * Create a contact from Lima.
     */
    public function lima(): static
    {
        return $this->state([
            'city' => 'Lima',
            'address_line' => $this->faker->randomElement([
                'Av. Javier Prado Este', 'Av. Larco', 'Av. Benavides',
                'Av. Arequipa', 'Av. Brasil', 'Av. Salaverry'
            ]) . ' ' . $this->faker->buildingNumber(),
        ]);
    }

    /**
     * Create a contact with international country.
     */
    public function international(): static
    {
        return $this->state([
            'country' => $this->faker->randomElement(['US', 'ES', 'AR', 'CL', 'CO']),
            'city' => $this->faker->city(),
            'phone' => $this->faker->unique()->numerify('+############'),
        ]);
    }

    /**
     * Generate a realistic Peruvian phone number.
     */
    private function generatePeruvianPhone(): string
    {
        // Mobile numbers in Peru start with 9 and have 9 digits
        if ($this->faker->boolean(80)) {
            return '9' . $this->faker->numberBetween(10000000, 99999999);
        }

        // Landline numbers (Lima area code 01)
        return '01' . $this->faker->numberBetween(1000000, 9999999);
    }
}
