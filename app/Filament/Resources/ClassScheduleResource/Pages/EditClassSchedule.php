<?php

namespace App\Filament\Resources\ClassScheduleResource\Pages;

use App\Filament\Resources\ClassScheduleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditClassSchedule extends EditRecord
{
    protected static string $resource = ClassScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('manageSeats')
                ->label('Gestionar Asientos')
                ->icon('heroicon-o-squares-plus')
                ->color('info')
                ->url(fn () => static::$resource::getUrl('manage-seats', ['record' => $this->record])),

            Actions\DeleteAction::make(),
        ];
    }
}
