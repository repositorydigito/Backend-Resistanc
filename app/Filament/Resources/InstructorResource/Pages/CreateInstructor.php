<?php

namespace App\Filament\Resources\InstructorResource\Pages;

use App\Filament\Resources\InstructorResource;
use App\Models\User;
use App\Models\Instructor;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class CreateInstructor extends CreateRecord
{
    protected static string $resource = InstructorResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        // Extraer datos del usuario del formulario
        $userEmail = $data['user_email'];
        $userPassword = $data['user_password'];

        // Validar que el email no exista ya
        if (User::where('email', $userEmail)->exists()) {
            Notification::make()
                ->title('Error')
                ->body('El correo electrónico ya está registrado.')
                ->danger()
                ->send();

            $this->halt();
        }

        // Crear el usuario primero
        $user = User::create([
            'name' => $data['name'],
            'email' => $userEmail,
            'password' => Hash::make($userPassword),
        ]);

        // Asignar el rol de Instructor al usuario
        $user->assignRole('Instructor');

        // Enviar email de verificación
        $user->sendEmailVerificationNotification();

        // Crear el instructor con el user_id
        $instructorData = array_merge($data, ['user_id' => $user->id]);

        // Remover los campos que no pertenecen al instructor
        unset($instructorData['user_email'], $instructorData['user_password'], $instructorData['user_password_confirmation']);

        return static::getModel()::create($instructorData);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Instructor creado exitosamente';
    }

    protected function getCreatedNotificationMessage(): ?string
    {
        return 'Se ha creado el usuario y el perfil del instructor correctamente.';
    }
}
