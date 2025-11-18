<?php

namespace Database\Seeders;

use App\Models\Package;

use App\Models\User;
use App\Models\UserPackage;
use App\Models\UserProfile;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class ClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🌱 Seeders de Clientes');
        $clienteRole = Role::firstOrCreate(['name' => 'Cliente']);

        // Clientes
        $clients = [];

        $user_cliente = User::create([
            'name' => 'Melany Mercedes',
            'email' => 'aizencode@gmail.com',
            'password' => bcrypt('123456789'),
            'email_verified_at' => now(),
        ]);
        $this->createUserProfile($user_cliente, 'Melany', 'Mercedes');
        $user_cliente->assignRole($clienteRole);
        $clients[] = $user_cliente;

        $user_cliente_two = User::create([
            'name' => 'Maryory Martines',
            'email' => 'maryory@gmail.com',
            'password' => bcrypt('123456789'),
            'email_verified_at' => now(),
        ]);
        $this->createUserProfile($user_cliente_two, 'Maryory', 'Martines');
        $user_cliente_two->assignRole($clienteRole);
        $clients[] = $user_cliente_two;

        $user_cliente_three = User::create([
            'name' => 'Ana Lucía Torres',
            'email' => 'ana@gmail.com',
            'password' => bcrypt('123456789'),
            'email_verified_at' => now(),
        ]);
        $this->createUserProfile($user_cliente_three, 'Ana Lucía', 'Torres');
        $user_cliente_three->assignRole($clienteRole);
        $clients[] = $user_cliente_three;

        $user_cliente_four = User::create([
            'name' => 'Maria Molina',
            'email' => 'maria@gmail.com',
            'password' => bcrypt('123456789'),
            'email_verified_at' => now(),
        ]);
        $this->createUserProfile($user_cliente_four, 'Maria', 'Molina');
        $user_cliente_four->assignRole($clienteRole);
        $clients[] = $user_cliente_four;
        // Fin clientes

        // cliente con metodo de pago
        // $method = $user_cliente->storedPaymentMethods()->create([
        //     'payment_type' => 'credit_card',
        //     'card_brand' => 'visa',
        //     'card_last_four' => '1111',
        //     'card_holder_name' => 'Juan Pérez',
        //     'card_expiry_month' => 12,
        //     'card_expiry_year' => 2025,
        //     'is_default' => false,
        //     'status' => 'active',
        //     'verification_status' => 'pending',
        //     'is_saved_for_future' => true,
        //     'billing_address' => [
        //         'street' => 'Av. Lima 123',
        //         'city' => 'Lima',
        //         'state' => 'Lima',
        //         'postal_code' => '15001',
        //         'country' => 'Perú',
        //     ],
        //     'gateway_token' => 'tok_test123456',
        //     'gateway_customer_id' => 'cus_fake123',
        // ]);
        // Fin cliente con metodo de pago

        // cliente paquete
        // $package1 = Package::firstWhere('id', 1); // o el id si lo conoces
        // $package2 = Package::firstWhere('id', 2);

        // foreach ($clients as $client) {
        //     if ($package1) {
        //         UserPackage::create([
        //             'user_id' => $client->id,
        //             'package_id' => $package1->id,
        //             'package_code' => sprintf('PCK-001-%03d', $client->id),
        //             'used_classes' => 0,
        //             'remaining_classes' => $package1->classes_quantity,
        //             'amount_paid_soles' => $package1->price_soles,
        //             'currency' => 'PEN',
        //             'purchase_date' => now(),
        //             'activation_date' => now(),
        //             'expiry_date' => $package1->duration_in_months
        //                 ? now()->copy()->addMonths($package1->duration_in_months)
        //                 : now()->copy()->addDays($package1->validity_days ?? 30), // Si no tiene duración en meses, usar validity_days o 30 días por defecto
        //             'status' => 'active',
        //         ]);
        //     }

        //     if ($package2) {
        //         UserPackage::create([
        //             'user_id' => $client->id,
        //             'package_id' => $package2->id,
        //             'package_code' => sprintf('PCK-002-%03d', $client->id),
        //             'used_classes' => 0,
        //             'remaining_classes' => $package2->classes_quantity,
        //             'amount_paid_soles' => $package2->price_soles,
        //             'currency' => 'PEN',
        //             'purchase_date' => now(),
        //             'activation_date' => now(),
        //             'expiry_date' => $package2->duration_in_months
        //                 ? now()->copy()->addMonths($package2->duration_in_months)
        //                 : now()->copy()->addDays($package2->validity_days ?? 30), // Si no tiene duración en meses, usar validity_days o 30 días por defecto
        //             'status' => 'active',
        //         ]);
        //     }
        // }
        // Fin cliente paquete
    }

    protected function createUserProfile($user, $first_name, $last_name)
    {
        UserProfile::create([
            'user_id' => $user->id,
            'first_name' => $first_name,
            'last_name' => $last_name,
            // 'email' => $email,
            'birth_date' => '1990-01-01',
            'gender' => 'female',
            'shoe_size_eu' => 38,

            'bio' => 'Una breve biografía del usuario.',
            'emergency_contact_name' => 'Contacto de Emergencia',
            'emergency_contact_phone' => '123456789',
            'medical_conditions' => 'Ninguna',
            'fitness_goals' => 'Mantenerse en forma y saludable.'
        ]);
    }
}
