<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;
use App\Models\User;

class EnsureClientRoleCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'roles:ensure-client 
                            {--create-test-users : Crear usuarios de prueba con rol Cliente}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Asegurar que existe el rol Cliente y opcionalmente crear usuarios de prueba';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔍 Verificando rol Cliente...');

        // Verificar si existe el rol Cliente
        $clientRole = Role::where('name', 'Cliente')->first();

        if (!$clientRole) {
            $this->warn('⚠️  El rol "Cliente" no existe. Creándolo...');
            
            $clientRole = Role::create([
                'name' => 'Cliente',
                'guard_name' => 'web'
            ]);
            
            $this->info('✅ Rol "Cliente" creado exitosamente.');
        } else {
            $this->info('✅ El rol "Cliente" ya existe.');
        }

        // Mostrar estadísticas
        $clientsCount = User::whereHas('roles', function($q) {
            $q->where('name', 'Cliente');
        })->count();

        $this->info("📊 Usuarios con rol Cliente: {$clientsCount}");

        // Crear usuarios de prueba si se solicita
        if ($this->option('create-test-users')) {
            $this->createTestUsers($clientRole);
        }

        // Mostrar algunos usuarios con rol Cliente
        $clients = User::whereHas('roles', function($q) {
            $q->where('name', 'Cliente');
        })->limit(5)->get();

        if ($clients->isNotEmpty()) {
            $this->info("\n👥 Algunos usuarios con rol Cliente:");
            $tableData = [];
            foreach ($clients as $client) {
                $tableData[] = [
                    'ID' => $client->id,
                    'Nombre' => $client->name,
                    'Email' => $client->email,
                    'Roles' => $client->roles->pluck('name')->join(', '),
                ];
            }
            $this->table(['ID', 'Nombre', 'Email', 'Roles'], $tableData);
        }

        return self::SUCCESS;
    }

    private function createTestUsers(Role $clientRole): void
    {
        $this->info("\n🧪 Creando usuarios de prueba con rol Cliente...");

        $testUsers = [
            [
                'name' => 'Cliente Test 1',
                'email' => 'cliente1@test.com',
                'password' => bcrypt('password123')
            ],
            [
                'name' => 'Cliente Test 2', 
                'email' => 'cliente2@test.com',
                'password' => bcrypt('password123')
            ],
            [
                'name' => 'Cliente Test 3',
                'email' => 'cliente3@test.com', 
                'password' => bcrypt('password123')
            ],
        ];

        $created = 0;
        foreach ($testUsers as $userData) {
            // Verificar si ya existe
            $existingUser = User::where('email', $userData['email'])->first();
            
            if (!$existingUser) {
                $user = User::create($userData);
                $user->assignRole($clientRole);
                $this->line("✅ Usuario creado: {$user->name} ({$user->email})");
                $created++;
            } else {
                // Si existe pero no tiene el rol, asignárselo
                if (!$existingUser->hasRole('Cliente')) {
                    $existingUser->assignRole($clientRole);
                    $this->line("🔄 Rol Cliente asignado a usuario existente: {$existingUser->name}");
                } else {
                    $this->line("⏭️  Usuario ya existe con rol Cliente: {$existingUser->name}");
                }
            }
        }

        if ($created > 0) {
            $this->info("🎉 Se crearon {$created} usuarios de prueba con rol Cliente.");
        }
    }
}
