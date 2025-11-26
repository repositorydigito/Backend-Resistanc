<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected $profileData = [];

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->profileData = $data['profile'] ?? [];
        unset($data['profile']);
        return $data;
    }

    protected function afterCreate(): void
    {
        if (!empty($this->profileData)) {
            $this->record->profile()->updateOrCreate(
                ['user_id' => $this->record->id],
                $this->profileData
            );
        }
    }
}
