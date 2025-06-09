<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Discipline;
use App\Models\Instructor;
use App\Models\Package;
use App\Models\ProductCategory;
use App\Models\ProductTag;
use App\Models\Studio;
use App\Models\StudioLocation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RsistancCompleteSeeder extends Seeder
{
    /**
     * Run the database seeds for the complete RSISTANC system.
     */
    public function run(): void
    {
        $this->command->info('🚀 Starting complete RSISTANC system seeding...');

        DB::transaction(function () {
            // 1. Seed basic data first
            $this->seedBasicData();

            // 2. Seed business data
            $this->seedBusinessData();

            // 3. Seed product data
            $this->seedProductData();

            // 4. Seed package data
            $this->seedPackageData();

            // 5. Seed products and orders
            $this->call(ProductsAndOrdersSeeder::class);

            // 6. Create relationships
            $this->createRelationships();
        });

        $this->command->info('🎉 Complete RSISTANC system seeding completed!');
        $this->displaySummary();
    }

    /**
     * Seed basic foundational data.
     */
    private function seedBasicData(): void
    {
        $this->command->info('📊 Seeding basic data...');

        // Seed disciplines
        $this->call(DisciplineSeeder::class);

        // Seed studio locations
        $this->call(StudioLocationSeeder::class);

        // Create studios for each location
        $this->createStudios();
    }

    /**
     * Seed business-related data.
     */
    private function seedBusinessData(): void
    {
        $this->command->info('👥 Seeding business data...');

        // Create instructors
        // $this->createInstructors();

        // Create classes will be done after we have all dependencies
    }

    /**
     * Seed product-related data.
     */
    private function seedProductData(): void
    {
        $this->command->info('🛍️ Seeding product data...');

        // Create product categories
        $this->createProductCategories();

        // Create product tags
        $this->createProductTags();

        // Products will be created with relationships later
    }

    /**
     * Seed package data.
     */
    private function seedPackageData(): void
    {
        $this->command->info('📦 Seeding package data...');

        $this->createPackages();
    }

    /**
     * Create studios for each location.
     */
    private function createStudios(): void
    {
        $this->command->info('🏢 Creating studios...');

        $locations = StudioLocation::all();

        foreach ($locations as $location) {
            // // Each location has multiple studios
            // Studio::factory()->cycling()->create(['location' => $location->name . ' - Planta Baja']);
            // // Studio::factory()->cycling()->create(['location' => $location->name . ' - Primer Piso']);
            // Studio::factory()->reformer()->create(['location' => $location->name . ' - Segundo Piso']);
            // // Studio::factory()->reformer()->create(['location' => $location->name . ' - Segundo Piso']);
            // Studio::factory()->mat()->create(['location' => $location->name . ' - Tercer Piso']);
            // Studio::factory()->multipurpose()->create(['location' => $location->name . ' - Tercer Piso']);

            // $this->command->line("✅ Created studios for: {$location->name}");
        }
    }

    /**
     * Create instructors.
     */
    private function createInstructors(): void
    {
        $this->command->info('👨‍🏫 Creating instructors...');

        // Create head coaches
        // Instructor::factory(5)->headCoach()->active()->create();

        // Create cycling instructors
        // Instructor::factory(8)->cycling()->active()->create();

        // Create reformer instructors
        // Instructor::factory(6)->reformer()->active()->create();

        // Create general instructors
        // Instructor::factory(12)->active()->create();

        // Create some inactive instructors
        // Instructor::factory(3)->create(['status' => 'inactive']);

        // $this->command->line("✅ Created " . Instructor::count() . " instructors");
    }

    /**
     * Create product categories.
     */
    private function createProductCategories(): void
    {
        $this->command->info('📂 Creating product categories...');

        $categories = [
            ['name' => 'Ropa Deportiva', 'slug' => 'ropa-deportiva'],
            ['name' => 'Accesorios', 'slug' => 'accesorios'],
            ['name' => 'Equipamiento', 'slug' => 'equipamiento'],
            ['name' => 'Nutrición', 'slug' => 'nutricion'],
            ['name' => 'Bienestar', 'slug' => 'bienestar'],
            ['name' => 'Membresías', 'slug' => 'membresias'],
        ];

        foreach ($categories as $index => $categoryData) {
            ProductCategory::factory()->create([
                'name' => $categoryData['name'],
                'slug' => $categoryData['slug'],
                'sort_order' => ($index + 1) * 10,
                'is_active' => true,
            ]);
        }

        $this->command->line("✅ Created " . ProductCategory::count() . " product categories");
    }

    /**
     * Create product tags.
     */
    private function createProductTags(): void
    {
        $this->command->info('🏷️ Creating product tags...');

        // Create essential tags
        ProductTag::factory()->nuevo()->create();
        ProductTag::factory()->bestseller()->create();
        ProductTag::factory()->oferta()->create();

        // Create additional tags (limited to avoid unique constraint issues)
        ProductTag::factory(10)->create();

        $this->command->line("✅ Created " . ProductTag::count() . " product tags");
    }

    /**
     * Create packages.
     */
    private function createPackages(): void
    {
        $this->command->info('📦 Creating packages...');

        // Create essential packages
        // Package::factory()->starter()->active()->create();
        // Package::factory()->premium()->active()->create();
        // Package::factory()->monthlyUnlimited()->active()->create();

        // Create additional packages
        Package::factory(8)->active()->create();

        $this->command->line("✅ Created " . Package::count() . " packages");
    }

    /**
     * Create relationships between models.
     */
    private function createRelationships(): void
    {
        $this->command->info('🔗 Creating relationships...');

        // Attach disciplines to instructors
        $this->attachDisciplinesToInstructors();

        $this->command->line("✅ Created model relationships");
    }

    /**
     * Attach disciplines to instructors.
     */
    private function attachDisciplinesToInstructors(): void
    {
        $disciplines = Discipline::all();
        $instructors = Instructor::all();

        // En database/seeders/RsistancCompleteSeeder.php línea 240
        foreach ($instructors as $instructor) {
            // Ahora specialties siempre será un array gracias al accessor
            $specialties = $instructor->specialties; // Ya no necesitas json_decode()

            // Attach corresponding disciplines
            foreach ($specialties as $disciplineId) {
                $discipline = $disciplines->find($disciplineId);
                if ($discipline) {
                    // ... resto del código
                }
            }
        }
    }

    /**
     * Display a summary of created data.
     */
    private function displaySummary(): void
    {
        $this->command->table(
            ['Model', 'Count'],
            [
                ['Disciplines', Discipline::count()],
                ['Studio Locations', StudioLocation::count()],
                ['Studios', Studio::count()],
                ['Instructors', Instructor::count()],
                ['Product Categories', ProductCategory::count()],
                ['Product Tags', ProductTag::count()],
                ['Packages', Package::count()],
            ]
        );

        $this->command->info('📈 System status:');
        $this->command->line('• Active disciplines: ' . Discipline::where('is_active', true)->count());
        $this->command->line('• Active studios: ' . Studio::where('is_active', true)->count());
        $this->command->line('• Active instructors: ' . Instructor::where('status', 'active')->count());
        $this->command->line('• Head coaches: ' . Instructor::where('is_head_coach', true)->count());
        $this->command->line('• Active packages: ' . Package::where('status', 'active')->count());
    }
}
