<?php

namespace App\Console\Commands;

use App\Models\ClassScheduleSeat;
use App\Models\UserPackage;
use Illuminate\Console\Command;

class CheckSeatAssignmentsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'seats:check {--schedule-id= : ID específico del horario} {--user-id= : ID específico del usuario}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verifica las asignaciones de asientos y sus paquetes asociados';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $scheduleId = $this->option('schedule-id');
        $userId = $this->option('user-id');

        $this->info('🔍 Verificando asignaciones de asientos...');

        $query = ClassScheduleSeat::with(['user', 'userPackage.package', 'classSchedule.class', 'seat']);
        
        if ($scheduleId) {
            $query->where('class_schedules_id', $scheduleId);
        }
        
        if ($userId) {
            $query->where('user_id', $userId);
        }

        $assignments = $query->get();
        
        $this->info("📦 Encontradas {$assignments->count()} asignaciones para verificar");

        $reservedAssignments = $assignments->where('status', 'reserved');
        $occupiedAssignments = $assignments->where('status', 'occupied');
        
        $this->info("🟡 Asientos reservados: {$reservedAssignments->count()}");
        $this->info("🔴 Asientos ocupados: {$occupiedAssignments->count()}");

        foreach ($assignments as $assignment) {
            $this->newLine();
            $this->line("Asiento ID: {$assignment->id}");
            $this->line("  Horario: {$assignment->classSchedule->class->name} ({$assignment->classSchedule->scheduled_date})");
            $this->line("  Asiento: {$assignment->seat->row}.{$assignment->seat->column}");
            $this->line("  Estado: {$assignment->status}");
            
            if ($assignment->user) {
                $this->line("  Usuario: {$assignment->user->name} (ID: {$assignment->user->id})");
            }
            
            if ($assignment->user_waiting_id) {
                $this->line("  Usuario en espera: {$assignment->user_waiting_id}");
            }
            
            if ($assignment->user_package_id) {
                $userPackage = $assignment->userPackage;
                if ($userPackage) {
                    $this->line("  Paquete: {$userPackage->package_code} ({$userPackage->package->name})");
                    $this->line("    Clases restantes: {$userPackage->remaining_classes}");
                    $this->line("    Clases usadas: {$userPackage->used_classes}");
                    $this->line("    Estado: {$userPackage->status}");
                    
                    // Verificar si el paquete está activo
                    if ($userPackage->status !== 'active') {
                        $this->warn("    ⚠️  Paquete no está activo");
                    }
                    
                    // Verificar si el paquete ha expirado
                    if ($userPackage->expiry_date && $userPackage->expiry_date->isPast()) {
                        $this->warn("    ⚠️  Paquete ha expirado");
                    }
                } else {
                    $this->error("    ❌ Paquete no encontrado (ID: {$assignment->user_package_id})");
                }
            } else {
                $this->warn("    ⚠️  Sin paquete asignado");
            }
            
            if ($assignment->reserved_at) {
                $this->line("  Reservado: {$assignment->reserved_at}");
            }
            
            if ($assignment->expires_at) {
                $this->line("  Expira: {$assignment->expires_at}");
                if ($assignment->expires_at->isPast()) {
                    $this->warn("    ⚠️  Reserva expirada");
                }
            }
        }

        // Verificar paquetes sin asignaciones
        $this->newLine();
        $this->info('🔍 Verificando paquetes activos...');
        
        $activePackages = UserPackage::where('status', 'active')
            ->where('remaining_classes', '>', 0)
            ->whereDate('expiry_date', '>=', now())
            ->get();
            
        $this->info("📦 Paquetes activos con clases disponibles: {$activePackages->count()}");
        
        foreach ($activePackages as $package) {
            $assignmentsCount = ClassScheduleSeat::where('user_package_id', $package->id)
                ->whereIn('status', ['reserved', 'occupied'])
                ->count();
                
            $this->line("Paquete {$package->package_code}: {$package->remaining_classes} clases restantes, {$assignmentsCount} asignaciones activas");
        }

        return 0;
    }
} 