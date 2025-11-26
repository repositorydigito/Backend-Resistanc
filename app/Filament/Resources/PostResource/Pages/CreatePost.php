<?php

namespace App\Filament\Resources\PostResource\Pages;

use App\Filament\Resources\PostResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePost extends CreateRecord
{
    protected static string $resource = PostResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Si el usuario no es administrador, asignar automáticamente su ID
        if (!auth()->user()?->hasRole('super_admin') && !auth()->user()?->hasRole('Administrador')) {
            $data['user_id'] = auth()->id();
        }

        return $data;
    }




}
