<?php

namespace App\Filament\Resources\InstructorResource\Pages;

use App\Filament\Resources\InstructorResource;
use App\Models\User;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Hash;

class EditInstructor extends EditRecord
{
    protected static string $resource = InstructorResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Agregar datos del usuario al formulario
        if ($this->record->user) {
            $data['user_email'] = $this->record->user->email;
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Actualizar el usuario si se cambió la contraseña
        if ($this->record->user && !empty($data['user_password_edit'])) {
            $this->record->user->update([
                'password' => Hash::make($data['user_password_edit']),
            ]);
        }

        // Remover campos del usuario que no van al instructor
        unset($data['user_email'], $data['user_password_edit'], $data['user_password_confirmation_edit']);

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
