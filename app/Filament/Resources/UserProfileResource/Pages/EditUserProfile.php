<?php

namespace App\Filament\Resources\UserProfileResource\Pages;

use App\Filament\Resources\UserProfileResource;
use App\Models\User;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditUserProfile extends EditRecord
{
    protected static string $resource = UserProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->requiresConfirmation()
                ->modalHeading('Eliminar cliente')
                ->modalDescription('¿Estás seguro de que quieres eliminar este cliente? Esta acción también eliminará el usuario asociado y no se puede deshacer.')
                ->modalSubmitActionLabel('Sí, eliminar')
                ->action(function ($record) {
                    // Eliminar el usuario relacionado primero
                    if ($record->user) {
                        $record->user->delete();
                    }
                    // Luego eliminar el perfil
                    $record->delete();
                }),
        ];
    }

        protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // Obtener el usuario asociado
        $user = $record->user;

        // Validar que el email no exista ya (excluyendo el usuario actual)
        $newEmail = $data['user_email'];
        if ($newEmail !== $user->email && User::where('email', $newEmail)->exists()) {
            Notification::make()
                ->title('Error')
                ->body('El correo electrónico ya está registrado.')
                ->danger()
                ->send();

            $this->halt();
        }

        // Actualizar datos del usuario
        $userData = [
            'name' => $data['first_name'] . ' ' . $data['last_name'],
            'email' => $newEmail,
        ];

        // Solo actualizar contraseña si se proporciona una nueva
        if (!empty($data['user_password_edit'])) {
            $userData['password'] = $data['user_password_edit'];
        }

        $user->update($userData);

        // Actualizar el perfil
        $profileData = $data;
        unset($profileData['user_email'], $profileData['user_password_edit'], $profileData['user_password_confirmation_edit']);

        $record->update($profileData);

        return $record;
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Cargar el email del usuario asociado
        $record = $this->getRecord();
        if ($record && $record->user) {
            $data['user_email'] = $record->user->email;
        }

        return $data;
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Cliente actualizado exitosamente';
    }

    protected function getSavedNotificationMessage(): ?string
    {
        return 'Se han actualizado los datos del usuario y el perfil del cliente correctamente.';
    }
}
