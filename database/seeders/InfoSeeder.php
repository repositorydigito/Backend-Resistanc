<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class InfoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin']);

        $user = User::create([
            'name' => 'Diego Miguel Saravia',
            'email' => 'migelo5511@gmail.com',
            'password' => bcrypt('123456789'),
        ]);

        $user->assignRole($superAdminRole);

        User::create([
            'name' => 'Danna Cervantes Quispe',
            'email' => 'danna@gmail.com',
            'password' => bcrypt('123456789'),
        ]);
    }
}
