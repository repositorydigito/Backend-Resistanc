<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ClassSchedule;
use App\Models\Discipline;
use App\Models\Package;
use App\Models\User;
use App\Models\UserPackage;
use App\Services\PackageValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PackageValidationTest extends TestCase
{
    use RefreshDatabase;

    private PackageValidationService $packageValidationService;
    private User $user;
    private Discipline $discipline;
    private Package $package;
    private ClassSchedule $classSchedule;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->packageValidationService = new PackageValidationService();
        
        // Crear datos de prueba
        $this->user = User::factory()->create();
        $this->discipline = Discipline::factory()->create(['name' => 'Yoga']);
        $this->package = Package::factory()->create([
            'discipline_id' => $this->discipline->id,
            'name' => 'Paquete Yoga Test',
            'classes_quantity' => 10,
            'status' => 'active'
        ]);
        
        $this->classSchedule = ClassSchedule::factory()->create([
            'class' => [
                'discipline_id' => $this->discipline->id
            ]
        ]);
    }

    public function test_user_with_valid_package_can_reserve(): void
    {
        // Crear paquete válido para el usuario
        UserPackage::factory()->create([
            'user_id' => $this->user->id,
            'package_id' => $this->package->id,
            'status' => 'active',
            'remaining_classes' => 5,
            'expiry_date' => now()->addDays(30)
        ]);

        $validation = $this->packageValidationService->validateUserPackagesForSchedule(
            $this->classSchedule, 
            $this->user->id
        );

        $this->assertTrue($validation['valid']);
        $this->assertCount(1, $validation['available_packages']);
        $this->assertEquals('Paquetes disponibles encontrados', $validation['message']);
    }

    public function test_user_without_packages_cannot_reserve(): void
    {
        $validation = $this->packageValidationService->validateUserPackagesForSchedule(
            $this->classSchedule, 
            $this->user->id
        );

        $this->assertFalse($validation['valid']);
        $this->assertCount(0, $validation['available_packages']);
        $this->assertStringContains('No tienes paquetes disponibles', $validation['message']);
    }

    public function test_user_with_expired_package_cannot_reserve(): void
    {
        // Crear paquete expirado
        UserPackage::factory()->create([
            'user_id' => $this->user->id,
            'package_id' => $this->package->id,
            'status' => 'active',
            'remaining_classes' => 5,
            'expiry_date' => now()->subDays(1) // Expirado
        ]);

        $validation = $this->packageValidationService->validateUserPackagesForSchedule(
            $this->classSchedule, 
            $this->user->id
        );

        $this->assertFalse($validation['valid']);
        $this->assertCount(0, $validation['available_packages']);
    }

    public function test_user_with_wrong_discipline_package_cannot_reserve(): void
    {
        // Crear disciplina diferente
        $otherDiscipline = Discipline::factory()->create(['name' => 'Cycling']);
        $otherPackage = Package::factory()->create([
            'discipline_id' => $otherDiscipline->id,
            'name' => 'Paquete Cycling Test'
        ]);

        // Crear paquete para disciplina diferente
        UserPackage::factory()->create([
            'user_id' => $this->user->id,
            'package_id' => $otherPackage->id,
            'status' => 'active',
            'remaining_classes' => 5,
            'expiry_date' => now()->addDays(30)
        ]);

        $validation = $this->packageValidationService->validateUserPackagesForSchedule(
            $this->classSchedule, 
            $this->user->id
        );

        $this->assertFalse($validation['valid']);
        $this->assertCount(0, $validation['available_packages']);
    }

    public function test_package_consumption_works_correctly(): void
    {
        // Crear paquete válido
        $userPackage = UserPackage::factory()->create([
            'user_id' => $this->user->id,
            'package_id' => $this->package->id,
            'status' => 'active',
            'remaining_classes' => 5,
            'used_classes' => 0,
            'expiry_date' => now()->addDays(30)
        ]);

        $consumption = $this->packageValidationService->consumeClassFromPackage(
            $this->user->id,
            $this->discipline->id
        );

        $this->assertTrue($consumption['success']);
        $this->assertEquals(1, $consumption['consumed_package']['classes_consumed']);

        // Verificar que se actualizó en la base de datos
        $userPackage->refresh();
        $this->assertEquals(4, $userPackage->remaining_classes);
        $this->assertEquals(1, $userPackage->used_classes);
    }

    public function test_package_consumption_fails_with_no_classes_remaining(): void
    {
        // Crear paquete sin clases restantes
        UserPackage::factory()->create([
            'user_id' => $this->user->id,
            'package_id' => $this->package->id,
            'status' => 'active',
            'remaining_classes' => 0,
            'used_classes' => 10,
            'expiry_date' => now()->addDays(30)
        ]);

        $consumption = $this->packageValidationService->consumeClassFromPackage(
            $this->user->id,
            $this->discipline->id
        );

        $this->assertFalse($consumption['success']);
        $this->assertEquals('No hay paquetes disponibles para consumir', $consumption['message']);
    }

    public function test_user_package_use_classes_method(): void
    {
        $userPackage = UserPackage::factory()->create([
            'user_id' => $this->user->id,
            'package_id' => $this->package->id,
            'status' => 'active',
            'remaining_classes' => 5,
            'used_classes' => 0,
            'expiry_date' => now()->addDays(30)
        ]);

        $result = $userPackage->useClasses(2);

        $this->assertTrue($result);
        $this->assertEquals(3, $userPackage->remaining_classes);
        $this->assertEquals(2, $userPackage->used_classes);
    }

    public function test_user_package_cannot_use_more_classes_than_available(): void
    {
        $userPackage = UserPackage::factory()->create([
            'user_id' => $this->user->id,
            'package_id' => $this->package->id,
            'status' => 'active',
            'remaining_classes' => 2,
            'used_classes' => 8,
            'expiry_date' => now()->addDays(30)
        ]);

        $result = $userPackage->useClasses(5); // Intentar usar más de las disponibles

        $this->assertFalse($result);
        $this->assertEquals(2, $userPackage->remaining_classes); // No debe cambiar
        $this->assertEquals(8, $userPackage->used_classes); // No debe cambiar
    }

    public function test_user_package_can_use_for_correct_discipline(): void
    {
        $userPackage = UserPackage::factory()->create([
            'user_id' => $this->user->id,
            'package_id' => $this->package->id,
            'status' => 'active',
            'remaining_classes' => 5,
            'expiry_date' => now()->addDays(30)
        ]);

        $userPackage->load('package'); // Cargar relación

        $canUse = $userPackage->canUseForDiscipline($this->discipline->id);
        $this->assertTrue($canUse);

        // Probar con disciplina incorrecta
        $otherDiscipline = Discipline::factory()->create();
        $cannotUse = $userPackage->canUseForDiscipline($otherDiscipline->id);
        $this->assertFalse($cannotUse);
    }
}
