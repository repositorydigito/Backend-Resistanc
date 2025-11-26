<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $profileData = $data['profile'] ?? [];
        unset($data['profile']);

        $user = $this->record;
        if (!empty($profileData)) {
            $user->profile()->updateOrCreate(
                ['user_id' => $user->id],
                $profileData
            );
        }

        return $data;
    }

    protected function fillForm(): void
    {
        $data = $this->record->attributesToArray();
        // Cargar datos del perfil si existe
        $data['profile'] = $this->record->profile ? $this->record->profile->toArray() : [];
        $this->form->fill($data);
    }
}
