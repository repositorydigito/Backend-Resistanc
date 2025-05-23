<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\LoginAudit;
use App\Models\SocialAccount;
use App\Models\User;
use App\Models\UserContact;
use App\Models\UserProfile;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RsistancSeeder extends Seeder
{
    private $faker;

    public function __construct()
    {
        $this->faker = Faker::create();
    }

    /**
     * Run the database seeds for RSISTANC application.
     */
    public function run(): void
    {
        $this->command->info('🚀 Seeding RSISTANC data...');

        DB::transaction(function () {
            // Create complete users with all related data
            $this->createCompleteUsers();

            // Create additional login audits for existing users
            $this->createAdditionalLoginAudits();

            // Create some users without complete profiles (testing scenarios)
            $this->createIncompleteUsers();
        });

        $this->command->info('✅ RSISTANC seeding completed successfully!');
        $this->displaySummary();
    }

    /**
     * Create users with complete profiles, contacts, and social accounts.
     */
    private function createCompleteUsers(): void
    {
        $this->command->info('👥 Creating complete users...');

        // Create 10 complete users with Google accounts
        for ($i = 1; $i <= 10; $i++) {
            $user = User::factory()->create();

            // Create profile
            $user->profile()->create([
                'first_name' => $this->faker->firstName(),
                'last_name' => $this->faker->lastName(),
                'birth_date' => $this->faker->dateTimeBetween('-60 years', '-18 years'),
                'gender' => $this->faker->randomElement(['female', 'male', 'other', 'na']),
                'shoe_size_eu' => $this->faker->numberBetween(35, 48),
            ]);

            // Create primary contact
            $user->contacts()->create([
                'phone' => '9' . str_pad((string)($i * 10000000 + rand(1000000, 9999999)), 8, '0', STR_PAD_LEFT),
                'address_line' => $this->faker->streetAddress(),
                'city' => 'Lima',
                'country' => 'PE',
                'is_primary' => true,
            ]);

            // Create social account
            $user->socialAccounts()->create([
                'provider' => 'google',
                'provider_uid' => 'google_' . $user->id . '_' . time() . '_' . $i,
                'provider_email' => $user->email,
                'token' => 'fake_token_' . $user->id,
                'token_expires_at' => now()->addYear(),
            ]);

            // Add login history
            LoginAudit::factory(rand(3, 8))->successful()->for($user)->create();
            LoginAudit::factory(rand(1, 2))->failed()->for($user)->create();
        }

        // Create 5 users with Facebook accounts
        for ($i = 11; $i <= 15; $i++) {
            $user = User::factory()->create();

            $user->profile()->create([
                'first_name' => $this->faker->firstNameFemale(),
                'last_name' => $this->faker->lastName(),
                'birth_date' => $this->faker->dateTimeBetween('-50 years', '-20 years'),
                'gender' => 'female',
                'shoe_size_eu' => $this->faker->numberBetween(35, 42),
            ]);

            $user->contacts()->create([
                'phone' => '9' . str_pad((string)($i * 10000000 + rand(1000000, 9999999)), 8, '0', STR_PAD_LEFT),
                'address_line' => $this->faker->streetAddress(),
                'city' => 'Lima',
                'country' => 'PE',
                'is_primary' => true,
            ]);

            $user->socialAccounts()->create([
                'provider' => 'facebook',
                'provider_uid' => 'fb_' . $user->id . '_' . time() . '_' . $i,
                'provider_email' => $user->email,
                'token' => 'fake_fb_token_' . $user->id,
                'token_expires_at' => now()->addMonths(6),
            ]);
        }

        // Create 5 senior users (no social accounts)
        for ($i = 16; $i <= 20; $i++) {
            $user = User::factory()->create();

            $user->profile()->create([
                'first_name' => $this->faker->firstName(),
                'last_name' => $this->faker->lastName(),
                'birth_date' => $this->faker->dateTimeBetween('-75 years', '-50 years'),
                'gender' => $this->faker->randomElement(['female', 'male']),
                'shoe_size_eu' => $this->faker->numberBetween(38, 45),
            ]);

            $user->contacts()->create([
                'phone' => '9' . str_pad((string)($i * 10000000 + rand(1000000, 9999999)), 8, '0', STR_PAD_LEFT),
                'address_line' => $this->faker->streetAddress(),
                'city' => $this->faker->randomElement(['Lima', 'Arequipa', 'Trujillo']),
                'country' => 'PE',
                'is_primary' => true,
            ]);
        }
    }

    /**
     * Create additional login audits for testing.
     */
    private function createAdditionalLoginAudits(): void
    {
        $this->command->info('📊 Creating login audit data...');

        // Create some suspicious login attempts
        LoginAudit::factory(20)->suspicious()->create();

        // Create recent mobile logins
        LoginAudit::factory(30)->recent()->mobile()->successful()->create();

        // Create desktop logins
        LoginAudit::factory(25)->desktop()->successful()->create();
    }

    /**
     * Create users with incomplete data for testing edge cases.
     */
    private function createIncompleteUsers(): void
    {
        $this->command->info('🔧 Creating test users with incomplete data...');

        // Users without profiles
        User::factory(5)->create();

        // Users with profiles but no contacts
        User::factory(3)
            ->has(UserProfile::factory(), 'profile')
            ->create();

        // Users with expired social tokens
        User::factory(2)
            ->has(UserProfile::factory(), 'profile')
            ->has(UserContact::factory()->primary(), 'contacts')
            ->create()
            ->each(function (User $user) {
                $user->socialAccounts()->create([
                    'provider' => 'google',
                    'provider_uid' => 'expired_' . $user->id . '_' . time(),
                    'provider_email' => $user->email,
                    'token' => 'expired_token_' . $user->id,
                    'token_expires_at' => now()->subMonths(6), // Expired
                ]);
            });
    }

    /**
     * Display a summary of created data.
     */
    private function displaySummary(): void
    {
        $this->command->table(
            ['Model', 'Count'],
            [
                ['Users', User::count()],
                ['User Profiles', UserProfile::count()],
                ['User Contacts', UserContact::count()],
                ['Social Accounts', SocialAccount::count()],
                ['Login Audits', LoginAudit::count()],
            ]
        );

        $this->command->info('📈 Data distribution:');
        $this->command->line('• Complete users: ' . User::whereHas('profile')->whereHas('contacts')->count());
        $this->command->line('• Users with social accounts: ' . User::whereHas('socialAccounts')->count());
        $this->command->line('• Recent login attempts: ' . LoginAudit::recent()->count());
        $this->command->line('• Successful logins: ' . LoginAudit::successful()->count());
        $this->command->line('• Failed logins: ' . LoginAudit::failed()->count());
    }
}
