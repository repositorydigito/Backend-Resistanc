<?php

namespace Database\Seeders;

use App\Models\Package;

use App\Models\User;
use App\Models\UserPackage;
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
        $user_cliente = User::create([
            'name' => 'Melany Mercedes',
            'email' => 'melany@gmail.com',
            'password' => bcrypt('123456789'),
        ]);
        $user_cliente->assignRole($clienteRole);

        $user_cliente_two = User::create([
            'name' => 'Maryory Martines',
            'email' => 'maryory@gmail.com',
            'password' => bcrypt('123456789'),
        ]);
        $user_cliente_two->assignRole($clienteRole);

        $user_cliente_three = User::create([
            'name' => 'Ana Lucía Torres',
            'email' => 'ana@gmail.com',
            'password' => bcrypt('123456789'),
        ]);
        $user_cliente_three->assignRole($clienteRole);

        $user_cliente_four = User::create([
            'name' => 'Maria Molina',
            'email' => 'maria@gmail.com',
            'password' => bcrypt('123456789'),
        ]);
        $user_cliente_four->assignRole($clienteRole);

        // Fin clientes



        // cliente con metodo de pago

        $method = $user_cliente->paymentMethods()->create([
            'payment_type' => 'credit_card',
            'card_brand' => 'visa',
            'card_last_four' => '1111',
            'card_holder_name' => 'Juan Pérez',
            'card_expiry_month' => 12,
            'card_expiry_year' => 2025,
            'is_default' => false,
            'status' => 'active',
            'verification_status' => 'pending',
            'is_saved_for_future' => true,
            'billing_address' => [
                'street' => 'Av. Lima 123',
                'city' => 'Lima',
                'state' => 'Lima',
                'postal_code' => '15001',
                'country' => 'Perú',
            ],
            'gateway_token' => 'tok_test123456',
            'gateway_customer_id' => 'cus_fake123',
        ]);

        // Fin cliente con metodo de pago

        // cliente paquete
        $package1 = Package::firstWhere('id', 1); // o el id si lo conoces
        $package2 = Package::firstWhere('id', 2);

        UserPackage::create([
            'user_id' => $user_cliente->id,
            'package_id' => $package1->id,
            'package_code' => 'PCK-001',
            'used_classes' => 0,
            'remaining_classes' => $package1->classes_quantity,
            'amount_paid_soles' => $package1->price_soles,
            'currency' => 'PEN',
            'purchase_date' => now(),
            'activation_date' => now(),
            'expiry_date' => now()->addDays($package1->validity_days),
            'status' => 'active',
        ]);

        UserPackage::create([
            'user_id' => $user_cliente->id,
            'package_id' => $package2->id,
            'package_code' => 'PCK-002',

            'used_classes' => 0,
            'remaining_classes' => $package2->classes_quantity,
            'amount_paid_soles' => $package2->price_soles,
            'currency' => 'PEN',
            'purchase_date' => now(),
            'activation_date' => now(),
            'expiry_date' => now()->addDays($package2->validity_days),
            'status' => 'active',
        ]);
        // Fin cliente paquete




    }
}
