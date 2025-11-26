<?php

namespace App\Filament\Resources\UserProfileResource\Pages;

use App\Filament\Resources\UserProfileResource;
use App\Models\User;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateUserProfile extends CreateRecord
{
    protected static string $resource = UserProfileResource::class;

    public function getTitle(): string
    {
        return 'Registrar Nuevo Cliente';
    }

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
            'name' => $data['first_name'] . ' ' . $data['last_name'],
            'email' => $userEmail,
            'password' => $userPassword,
        ]);

        // Asignar el rol de cliente al usuario
        $user->assignRole('Cliente');

        // Enviar email de verificación
        $user->sendEmailVerificationNotification();

        // Crear el perfil del usuario con el user_id
        $profileData = array_merge($data, ['user_id' => $user->id]);

        // Remover los campos que no pertenecen al perfil
        unset($profileData['user_email'], $profileData['user_password'], $profileData['user_password_confirmation']);

        return static::getModel()::create($profileData);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Cliente creado exitosamente';
    }

    protected function getCreatedNotificationMessage(): ?string
    {
        return 'Se ha creado el usuario y el perfil del cliente correctamente.';
    }
}
