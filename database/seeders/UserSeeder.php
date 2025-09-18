<?php

namespace Database\Seeders;


use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $this->command->info('🌱 Seeders de Usuarios');

        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin']);

        $AdminRole = Role::firstOrCreate(['name' => 'Administrador']);

        // Obtener todos los permisos
        $permissions = Permission::all();

        // Asignar todos los permisos al rol
        $AdminRole->syncPermissions($permissions);

        $administrador = User::create([
            'name' => 'Alexie Garcia',
            'email' => 'rsistanc@gmail.com',
            'password' => bcrypt('123456789'),
        ]);
        $administrador->assignRole($AdminRole);

        $user_admin = User::create([
            'name' => 'Diego Miguel Saravia',
            'email' => 'migelo5511@gmail.com',
            'password' => bcrypt('123456789'),
        ]);

        $user_admin->assignRole($superAdminRole);
    }
}
