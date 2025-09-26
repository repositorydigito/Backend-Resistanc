<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Discipline;
use App\Models\LegalPolicy;
use App\Models\Product;
use App\Models\ProductBrand;
use App\Models\Studio;
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

        // Usuarios
        $this->call(UserSeeder::class);

        // Disciplinas
        $this->call(DisciplineSeeder::class);

        // Membresías
        $this->call(MembershipSeeder::class);

        // Instructor
        $this->call(InstructorSeeder::class);

        // Clases
        $this->call(ClassModelSeeder::class);

        // Salas o estudios
        $this->call(StudioSeeder::class);

        // Paquetes
        $this->call(PackageSeeder::class);

        // Clientes
        $this->call(ClientSeeder::class);

        // Tipos de shake
        $this->call(TypeDrinkSeeder::class);

        // Sabores de shake
        $this->call(FlavorDrinkSeeder::class);

        // Base del shake
        $this->call(BaseDrinkSeeder::class);

        // Shake
        $this->call(DrinkSeeder::class);

        // Horarios
        $this->call(ClassScheduleSeeder::class);

        // Marcas de los productos
        $this->call(ProductBrandSeeder::class);

        // Categoria del producto
        $this->call(ProductCategorySeeder::class);

        // Etiquetas del producto
        $this->call(ProductTagSeeder::class);

        // Productos
        $this->call(ProductSeeder::class);

        // Opciones de variacion del producto
        $this->call(ProductOptionTypeSeeder::class);

        // Variaciones del producto
        $this->call(VariantOptionSeeder::class);

        //Informacion de la empresa
        $this->call(CompanySeeder::class);

        // Calzado
        $this->call(FootwearSeeder::class);

        // Toallas
        $this->call(TowelSeeder::class);

        // Etiquetas
        $this->call(TagSeeder::class);

        // Categorías
        $this->call(CategorySeeder::class);

        // Posts
        $this->call(PostSeeder::class);

        // Plantillas de email
        $this->call(TemplateEmailSeeder::class);

        // Preguntas frecuentes
        $this->call(FaqSeeder::class);

        // Servicios
        $this->call(ServiceSeeder::class);

        // Politicas y privacidad
        $this->call(LegalPolicySeeder::class);


        $this->command->info('🎉 Database seeding completed!');
    }
}
