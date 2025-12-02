<?php

namespace App\Filament\Resources\ClassScheduleResource\Pages;

use App\Filament\Resources\ClassScheduleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditClassSchedule extends EditRecord
{
    protected static string $resource = ClassScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\Action::make('manageSeats')
            //     ->label('Gestionar Asientos')
            //     ->icon('heroicon-o-squares-plus')
            //     ->color('info')
            //     ->url(fn () => static::$resource::getUrl('manage-seats', ['record' => $this->record])),

            Actions\DeleteAction::make(),


        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Validar que no exista otro horario con la misma clase, fecha y hora de inicio
        // Excluir el registro actual
        $existing = \App\Models\ClassSchedule::where('class_id', $data['class_id'])
            ->whereDate('scheduled_date', $data['scheduled_date'])
            ->where('start_time', $data['start_time'])
            ->where('status', '!=', 'cancelled') // Excluir cancelados
            ->where('id', '!=', $this->record->id) // Excluir el registro actual
            ->first();

        // COMENTADO: Causa error de JSON en Livewire
        // if ($existing) {
        //     $class = $existing->class->name ?? 'Clase';
        //     Notification::make()
        //         ->title('Error: Horario duplicado')
        //         ->body("Ya existe otro horario para esta clase el {$data['scheduled_date']} a las {$data['start_time']}. Horario ID: {$existing->id}")
        //         ->danger()
        //         ->persistent()
        //         ->send();
        //     
        //     $this->halt(); // Detener el proceso de actualización
        // }

        return $data;
    }

    protected function afterSave(): void
    {
        // Verificar si se cambió la sala
        if ($this->record->wasChanged('studio_id')) {
            $seatsGenerated = $this->record->seatAssignments()->count();

            Notification::make()
                ->title('🔄 Sala actualizada')
                ->body("Se regeneraron {$seatsGenerated} asientos para la nueva sala.")
                ->success()
                ->duration(5000)
                ->send();
        }

        // Verificar si se activó el envío de correos de reemplazo
        if ($this->record->wasChanged('replaced_email') && $this->record->replaced_email === true) {
            try {
                // Cargar relaciones necesarias
                $this->record->load(['class.discipline', 'instructor', 'substituteInstructor', 'seatAssignments.user']);
                
                // Verificar que tenga instructor suplente
                if (!$this->record->substitute_instructor_id || !$this->record->substituteInstructor) {
                    $this->record->update(['replaced_email' => false]);
                    Notification::make()
                        ->title('Error')
                        ->body('No se ha configurado un instructor suplente.')
                        ->danger()
                        ->send();
                    return;
                }

                // Obtener todos los usuarios únicos con asientos reservados
                $reservedSeats = $this->record->seatAssignments()
                    ->whereIn('status', ['reserved', 'occupied'])
                    ->whereNotNull('user_id')
                    ->with('user')
                    ->get();

                $users = $reservedSeats->pluck('user')->filter()->unique('id');

                if ($users->isEmpty()) {
                    $this->record->update(['replaced_email' => false]);
                    Notification::make()
                        ->title('Sin estudiantes')
                        ->body('No hay estudiantes inscritos en esta clase.')
                        ->warning()
                        ->send();
                    return;
                }

                // Dividir usuarios en grupos de 50 (límite de BCC)
                $userGroups = $users->chunk(50);
                $totalGroups = $userGroups->count();
                $sentCount = 0;

                // Enviar correos por grupos
                foreach ($userGroups as $groupIndex => $userGroup) {
                    $emails = $userGroup->pluck('email')->filter()->toArray();
                    
                    if (empty($emails)) {
                        continue;
                    }

                    $primaryUser = $userGroup->first();
                    $bccEmails = array_slice($emails, 1);

                    try {
                        $mail = new \App\Mail\InstructorReplacedMailable($primaryUser, $this->record);
                        
                        if (!empty($bccEmails)) {
                            $mail->bcc($bccEmails);
                        }
                        
                        \Illuminate\Support\Facades\Mail::send($mail);
                        $sentCount += count($emails);
                        
                        \Illuminate\Support\Facades\Log::info('Correos de reemplazo enviados', [
                            'class_schedule_id' => $this->record->id,
                            'group' => $groupIndex + 1,
                            'total_groups' => $totalGroups,
                            'emails_sent' => count($emails),
                        ]);
                        
                        if ($groupIndex < $totalGroups - 1) {
                            usleep(500000);
                        }
                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::error('Error enviando correo de reemplazo', [
                            'class_schedule_id' => $this->record->id,
                            'group' => $groupIndex + 1,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                Notification::make()
                    ->title('Correos enviados')
                    ->body("Se enviaron {$sentCount} correo(s) a los estudiantes inscritos en {$totalGroups} grupo(s).")
                    ->success()
                    ->send();

            } catch (\Exception $e) {
                $this->record->update(['replaced_email' => false]);
                \Illuminate\Support\Facades\Log::error('Error al enviar correos de reemplazo', [
                    'class_schedule_id' => $this->record->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                Notification::make()
                    ->title('Error')
                    ->body('Ocurrió un error al enviar los correos: ' . $e->getMessage())
                    ->danger()
                    ->send();
            }
        }

        // Emitir evento JavaScript para actualizar componentes Livewire
        $this->dispatch('schedule-updated', scheduleId: $this->record->id);
    }
}
