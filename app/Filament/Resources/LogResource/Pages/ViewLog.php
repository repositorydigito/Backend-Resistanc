<?php

namespace App\Filament\Resources\LogResource\Pages;

use App\Filament\Resources\LogResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Divider;

class ViewLog extends ViewRecord
{
    protected static string $resource = LogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No permitir editar ni eliminar logs
        ];
    }

    protected function getInfolistSchema(): array
    {
        return [
            Section::make('Información del Log')
                ->schema([
                    Grid::make(2)->schema([
                        TextEntry::make('user_id')
                            ->label('ID Usuario')
                            ->numeric(),
                        TextEntry::make('user.name')
                            ->label('Usuario')
                            ->default('N/A'),
                    ]),
                    TextEntry::make('action')
                        ->label('Acción')
                        ->columnSpanFull(),
                    TextEntry::make('description')
                        ->label('Descripción')
                        ->columnSpanFull(),
                ]),

            Divider::make(),

            Section::make('Datos Adicionales')
                ->schema([
                    TextEntry::make('data_formatted')
                        ->label('')
                        ->formatStateUsing(function ($record) {
                            if (!$record || !$record->data) {
                                return 'Sin datos adicionales';
                            }
                            
                            $data = $record->data;
                            $json = '';
                            
                            // Si es un array u objeto, formatear como JSON
                            if (is_array($data) || is_object($data)) {
                                $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                            } elseif (is_string($data)) {
                                // Si es un string, verificar si es JSON válido
                                $decoded = json_decode($data, true);
                                if (json_last_error() === JSON_ERROR_NONE) {
                                    $json = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                                } else {
                                    $json = $data;
                                }
                            } else {
                                $json = print_r($data, true);
                            }
                            
                            // Retornar HTML formateado con estilos inline
                            return '<div style="background-color: #f3f4f6; padding: 1rem; border-radius: 0.5rem; overflow-x: auto; font-family: \'Courier New\', monospace; font-size: 0.875rem; line-height: 1.5; white-space: pre-wrap; word-wrap: break-word; max-height: 600px; overflow-y: auto;">' . 
                                   htmlspecialchars($json, ENT_QUOTES, 'UTF-8') . 
                                   '</div>';
                        })
                        ->html()
                        ->columnSpanFull(),
                ])
                ->visible(fn ($record) => !empty($record->data)),

            Divider::make(),

            Section::make('Metadatos')
                ->schema([
                    Grid::make(2)->schema([
                        TextEntry::make('created_at')
                            ->label('Fecha de Creación')
                            ->dateTime('d/m/Y H:i:s'),
                        TextEntry::make('updated_at')
                            ->label('Última Actualización')
                            ->dateTime('d/m/Y H:i:s'),
                    ]),
                ])
                ->collapsible(),
        ];
    }
}




