<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\BadgeEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Divider;

class ViewInvoice extends ViewRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderWidgets(): array
    {
        return [];
    }

    protected function getInfolistSchema(): array
    {
        return [
            Section::make('Estado y SUNAT')
                ->schema([
                    Grid::make(3)->schema([
                        BadgeEntry::make('envio_estado')
                            ->label('Estado de envío')
                            ->colors([
                                'success' => 'enviada',
                                'danger' => 'fallida',
                                'warning' => 'pendiente',
                            ]),
                        IconEntry::make('enviada_a_nubefact')
                            ->label('¿Enviada a Nubefact?'),
                        IconEntry::make('aceptada_por_sunat')
                            ->label('Aceptada por SUNAT'),
                    ]),
                    TextEntry::make('sunat_description')->label('Descripción SUNAT'),
                    TextEntry::make('error_envio')
                        ->label('Error de envío')
                        ->visible(fn ($record) => !empty($record->error_envio))
                        ->color('danger'),
                ]),

            Divider::make(),

            Section::make('Datos del Cliente')
                ->schema([
                    Grid::make(2)->schema([
                        TextEntry::make('cliente_denominacion')->label('Cliente'),
                        TextEntry::make('cliente_numero_de_documento')->label('N° Documento'),
                        TextEntry::make('cliente_direccion')->label('Dirección'),
                        TextEntry::make('cliente_email')->label('Email'),
                    ]),
                ]),

            Divider::make(),

            Section::make('Comprobante')
                ->schema([
                    Grid::make(2)->schema([
                        TextEntry::make('serie')->label('Serie'),
                        TextEntry::make('numero')->label('Número'),
                        TextEntry::make('fecha_de_emision')->label('Fecha de emisión'),
                        TextEntry::make('moneda')->label('Moneda'),
                    ]),
                ]),

            Divider::make(),

            Section::make('Totales')
                ->schema([
                    Grid::make(3)->schema([
                        TextEntry::make('total_gravada')->label('Total Gravada')->money('PEN'),
                        TextEntry::make('total_igv')->label('Total IGV')->money('PEN'),
                        TextEntry::make('total')->label('Total')->money('PEN'),
                    ]),
                ]),

            Divider::make(),

            Section::make('Archivos')
                ->schema([
                    Grid::make(3)->schema([
                        TextEntry::make('enlace_del_pdf')
                            ->label('PDF')
                            ->url(fn ($record) => $record->enlace_del_pdf, true)
                            ->openUrlInNewTab()
                            ->visible(fn ($record) => !empty($record->enlace_del_pdf)),
                        TextEntry::make('enlace_del_xml')
                            ->label('XML')
                            ->url(fn ($record) => $record->enlace_del_xml, true)
                            ->openUrlInNewTab()
                            ->visible(fn ($record) => !empty($record->enlace_del_xml)),
                        TextEntry::make('enlace_del_cdr')
                            ->label('CDR')
                            ->url(fn ($record) => $record->enlace_del_cdr, true)
                            ->openUrlInNewTab()
                            ->visible(fn ($record) => !empty($record->enlace_del_cdr)),
                    ]),
                ]),

            Divider::make(),

            Section::make('Detalle de Items')
                ->schema([
                    RepeatableEntry::make('items')
                        ->label('Items de la factura')
                        ->schema([
                            Grid::make(4)->schema([
                                TextEntry::make('codigo')->label('Código'),
                                TextEntry::make('descripcion')->label('Descripción'),
                                TextEntry::make('cantidad')->label('Cantidad'),
                                TextEntry::make('valor_unitario')->label('Valor Unitario')->money('PEN'),
                                TextEntry::make('precio_unitario')->label('Precio Unitario')->money('PEN'),
                                TextEntry::make('subtotal')->label('Subtotal')->money('PEN'),
                                TextEntry::make('igv')->label('IGV')->money('PEN'),
                                TextEntry::make('total')->label('Total')->money('PEN'),
                            ]),
                        ])
                        ->visible(fn ($record) => $record->items && count($record->items) > 0),
                ]),
        ];
    }
}
