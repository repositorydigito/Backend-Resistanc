<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('🌱 Starting RSISTANC database seeding...');

        // Create admin/test user first
        $this->createAdminUser();

        // Run the main RSISTANC user seeder
        $this->call(RsistancSeeder::class);

        // Run the complete system seeder
        $this->call(RsistancCompleteSeeder::class);

        // Run the info seeder
        $this->call(InfoSeeder::class);

        $this->command->info('🎉 Database seeding completed!');
    }

    /**
     * Create the main admin/test user.
     */
    private function createAdminUser(): void
    {
        $this->command->info('👑 Creating admin user...');

        $adminUser = User::factory()->create([
            'name' => 'Admin RSISTANC',
            'email' => 'admin@rsistanc.com',
        ]);

        // Create complete profile for admin
        $adminUser->profile()->create([
            'first_name' => 'Admin',
            'last_name' => 'RSISTANC',
            'birth_date' => now()->subYears(30),
            'gender' => 'male',
            'shoe_size_eu' => 42,
        ]);

        // Create primary contact for admin
        $adminUser->contacts()->create([
            'phone' => '987654321',
            'address_line' => 'Av. Javier Prado Este 123',
            'city' => 'Lima',
            'country' => 'PE',
            'is_primary' => true,
        ]);

        $this->command->line("✅ Admin user created: {$adminUser->email}");
    }
}
