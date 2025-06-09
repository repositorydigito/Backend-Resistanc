<?php

namespace App\Filament\Resources\ClassScheduleResource\Pages;

use App\Filament\Resources\ClassScheduleResource;
use App\Imports\ClassSchedulesImport;
use Exception;
use Filament\Actions;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Maatwebsite\Excel\Facades\Excel;

class ListClassSchedules extends ListRecords
{
    protected static string $resource = ClassScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [

            Actions\Action::make('importar_horarios')
                ->label('Importar horarios')
                ->icon('heroicon-o-arrow-up-tray')
                ->form([
                    FileUpload::make('file')
                        ->label('Archivo Excel/CSV')
                        ->required()
                        ->maxSize(2048) // 2MB
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // .xlsx
                            'application/vnd.ms-excel', // .xls
                            'text/csv' // .csv
                        ])
                        ->helperText('Formatos permitidos: .xlsx, .xls, .csv (máximo 2MB)')
                        ->disk('local')
                        ->directory('imports'),
                ])
                ->action(function (array $data) {
                    // Configurar límites para archivos grandes
                    ini_set('max_execution_time', 300); // 5 minutos
                    ini_set('memory_limit', '512M');

                    try {
                        $import = new ClassSchedulesImport();
                        Excel::import($import, $data['file']);

                        // Obtener errores y advertencias del importador
                        $errors = $import->getErrors();
                        $warnings = $import->getWarnings();

                        if (!empty($errors)) {
                            // Mostrar errores detallados
                            $errorMessage = "Se encontraron errores en el archivo:\n\n";

                            // Mostrar máximo 10 errores para no saturar la pantalla
                            $displayErrors = array_slice($errors, 0, 10);
                            foreach ($displayErrors as $error) {
                                $errorMessage .= "• " . $error . "\n";
                            }

                            if (count($errors) > 10) {
                                $errorMessage .= "\n... y " . (count($errors) - 10) . " errores más.\n";
                            }

                            $errorMessage .= "\n💡 Revisa el archivo y corrige los errores antes de volver a importar.";

                            Notification::make()
                                ->title('❌ Error en la importación')
                                ->body($errorMessage)
                                ->danger()
                                ->persistent() // No se oculta automáticamente
                                ->actions([
                                    \Filament\Notifications\Actions\Action::make('Descargar plantilla')
                                        ->button()
                                        ->url(asset('plantillas/horarios.xlsx'))
                                        ->openUrlInNewTab(),
                                ])
                                ->send();

                            return; // Detener ejecución
                        }

                        // Si no hay errores, mostrar éxito
                        $successMessage = "✅ Horarios importados exitosamente!";

                        // Agregar advertencias si las hay
                        if (!empty($warnings)) {
                            $successMessage .= "\n\n⚠️ Advertencias encontradas:\n";
                            $displayWarnings = array_slice($warnings, 0, 5);
                            foreach ($displayWarnings as $warning) {
                                $successMessage .= "• " . $warning . "\n";
                            }

                            if (count($warnings) > 5) {
                                $successMessage .= "... y " . (count($warnings) - 5) . " advertencias más.\n";
                            }
                        }

                        Notification::make()
                            ->title('🎉 Importación completada')
                            ->body($successMessage)
                            ->success()
                            ->persistent()
                            ->send();

                        // Refrescar la página para mostrar los nuevos registros
                        $this->redirect(request()->header('Referer'));
                    } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
                        // Errores de validación de Laravel Excel
                        $failures = $e->failures();
                        $errorMessage = "Errores de validación encontrados:\n\n";

                        foreach ($failures as $failure) {
                            $errorMessage .= "• FILA {$failure->row()}: " . implode(', ', $failure->errors()) . "\n";
                        }

                        Notification::make()
                            ->title('❌ Errores de validación')
                            ->body($errorMessage)
                            ->danger()
                            ->persistent()
                            ->send();
                    } catch (Exception $e) {
                        // Error general
                        $errorMessage = "Error inesperado durante la importación:\n\n";
                        $errorMessage .= $e->getMessage();
                        $errorMessage .= "\n\n💡 Verifica que el archivo tenga el formato correcto y todos los campos requeridos.";

                        Notification::make()
                            ->title('❌ Error al importar horarios')
                            ->body($errorMessage)
                            ->danger()
                            ->persistent()
                            ->actions([
                                \Filament\Notifications\Actions\Action::make('Ver logs')
                                    ->button()
                                    ->action(function () {
                                        // Opcionalmente, redirigir a los logs
                                        logger()->error('Error en importación de horarios', [
                                            'message' => $e->getMessage(),
                                            'trace' => $e->getTraceAsString()
                                        ]);
                                    }),
                                \Filament\Notifications\Actions\Action::make('Descargar plantilla')
                                    ->button()
                                    ->url(asset('plantillas/horarios.xlsx'))
                                    ->openUrlInNewTab(),
                            ])
                            ->send();
                    }
                })
                ->color('info')
                ->requiresConfirmation()
                ->modalHeading('Importar horarios de clases')
                ->modalDescription('Sube un archivo Excel o CSV con los horarios. Asegúrate de usar la plantilla correcta.')
                ->modalSubmitActionLabel('Importar')
                ->modalIcon('heroicon-o-arrow-up-tray'),
            Actions\Action::make('Descargar plantilla')
                ->url(asset('plantillas/horarios.xlsx'))
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray'),


            Actions\CreateAction::make(),
        ];
    }
}
