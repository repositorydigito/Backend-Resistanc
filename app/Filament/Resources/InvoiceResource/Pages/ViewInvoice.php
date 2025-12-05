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

    public function mount(int | string $record): void
    {
        parent::mount($record);
        
        // Asegurar que los items se carguen
        $this->record->load('items');
    }

    protected function getInfolistSchema(): array
    {
        return [
            Section::make('Comprobante')
                ->schema([
                    Grid::make(3)->schema([
                        TextEntry::make('tipo_comprobante_nombre')
                            ->label('Tipo de Comprobante')
                            ->badge()
                            ->color(fn ($record) => $record->isFactura() ? 'info' : 'success'),
                        TextEntry::make('serie')
                            ->label('Serie del Comprobante')
                            ->formatStateUsing(fn ($record) => $record->serie ?? 'N/A'),
                        TextEntry::make('numero')
                            ->label('Número del Comprobante')
                            ->formatStateUsing(fn ($record) => $record->numero ?? 'N/A'),
                        TextEntry::make('numero_completo')
                            ->label('Número Completo (Serie-Número)')
                            ->formatStateUsing(fn ($record) => "{$record->serie}-{$record->numero}")
                            ->columnSpanFull()
                            ->weight('bold'),
                        TextEntry::make('fecha_de_emision')
                            ->label('Fecha de Emisión')
                            ->date('d/m/Y'),
                    ]),
                ]),

            Divider::make(),

            Section::make('Datos del Cliente')
                ->schema([
                    Grid::make(2)->schema([
                        TextEntry::make('cliente_denominacion')
                            ->label('Nombre / Razón Social del Cliente'),
                        TextEntry::make('cliente_tipo_documento_nombre')
                            ->label('Tipo de Documento del Cliente')
                            ->badge()
                            ->color('gray')
                            ->formatStateUsing(fn ($record) => $record->cliente_tipo_documento_nombre ?? 'N/A'),
                        TextEntry::make('cliente_numero_de_documento')
                            ->label('Número de Documento (RUC/DNI)')
                            ->formatStateUsing(fn ($record) => $record->cliente_numero_de_documento ?? 'N/A'),
                        TextEntry::make('cliente_direccion')
                            ->label('Dirección del Cliente')
                            ->columnSpanFull()
                            ->visible(fn ($record) => !empty($record->cliente_direccion)),
                        TextEntry::make('cliente_email')
                            ->label('Email del Cliente')
                            ->icon('heroicon-o-envelope')
                            ->visible(fn ($record) => !empty($record->cliente_email)),
                    ]),
                ]),

            Divider::make(),

            Section::make('Totales del Comprobante')
                ->schema([
                    Grid::make(3)->schema([
                        TextEntry::make('total_gravada')
                            ->label('Total Gravada (Base Imponible)')
                            ->money('PEN')
                            ->visible(fn ($record) => $record->total_gravada > 0),
                        TextEntry::make('total_igv')
                            ->label('Total IGV (Impuesto General a las Ventas)')
                            ->money('PEN')
                            ->visible(fn ($record) => $record->total_igv > 0),
                        TextEntry::make('total')
                            ->label('Total a Pagar')
                            ->money('PEN')
                            ->weight('bold')
                            ->size('lg'),
                    ]),
                ]),

            Divider::make(),

            Section::make('Estado de Envío a SUNAT')
                ->schema([
                    Grid::make(2)->schema([
                        BadgeEntry::make('envio_estado')
                            ->label('Estado de Envío')
                            ->colors([
                                'success' => 'enviada',
                                'danger' => 'fallida',
                                'warning' => 'pendiente',
                            ]),
                        IconEntry::make('aceptada_por_sunat')
                            ->label('¿Aceptada por SUNAT?')
                            ->boolean()
                            ->trueIcon('heroicon-o-check-circle')
                            ->falseIcon('heroicon-o-x-circle')
                            ->trueColor('success')
                            ->falseColor('danger'),
                    ]),
                    TextEntry::make('sunat_description')
                        ->label('Descripción de SUNAT')
                        ->color('info')
                        ->visible(fn ($record) => !empty($record->sunat_description)),
                    TextEntry::make('error_envio')
                        ->label('Error de Envío')
                        ->color('danger')
                        ->icon('heroicon-o-exclamation-triangle')
                        ->visible(fn ($record) => !empty($record->error_envio)),
                ]),

            Divider::make(),

            Section::make('Archivos del Comprobante')
                ->schema([
                    Grid::make(3)->schema([
                        TextEntry::make('enlace_del_pdf')
                            ->label('PDF del Comprobante')
                            ->url(fn ($record) => $record->enlace_del_pdf, true)
                            ->openUrlInNewTab()
                            ->icon('heroicon-o-document-arrow-down')
                            ->color('danger')
                            ->helperText('Descargar PDF')
                            ->visible(fn ($record) => !empty($record->enlace_del_pdf)),
                        TextEntry::make('enlace_del_xml')
                            ->label('XML del Comprobante')
                            ->url(fn ($record) => $record->enlace_del_xml, true)
                            ->openUrlInNewTab()
                            ->icon('heroicon-o-document-arrow-down')
                            ->color('info')
                            ->helperText('Descargar XML')
                            ->visible(fn ($record) => !empty($record->enlace_del_xml)),
                        TextEntry::make('enlace_del_cdr')
                            ->label('CDR (Constancia de Recepción)')
                            ->url(fn ($record) => $record->enlace_del_cdr, true)
                            ->openUrlInNewTab()
                            ->icon('heroicon-o-document-arrow-down')
                            ->color('success')
                            ->helperText('Descargar CDR de SUNAT')
                            ->visible(fn ($record) => !empty($record->enlace_del_cdr)),
                    ]),
                ])
                ->visible(fn ($record) => !empty($record->enlace_del_pdf) || !empty($record->enlace_del_xml) || !empty($record->enlace_del_cdr)),

            Divider::make(),

            Section::make('Items del Comprobante')
                ->schema([
                    RepeatableEntry::make('items')
                        ->schema([
                            Grid::make(6)->schema([
                                TextEntry::make('codigo')
                                    ->label('Código')
                                    ->badge()
                                    ->color('gray'),
                                TextEntry::make('descripcion')
                                    ->label('Descripción del Producto/Servicio')
                                    ->columnSpan(2),
                                TextEntry::make('unidad_de_medida')
                                    ->label('Unidad')
                                    ->badge()
                                    ->color('info'),
                                TextEntry::make('cantidad')
                                    ->label('Cantidad')
                                    ->numeric()
                                    ->suffix(' unidades'),
                                TextEntry::make('precio_unitario')
                                    ->label('Precio Unitario')
                                    ->money('PEN'),
                                TextEntry::make('igv')
                                    ->label('IGV')
                                    ->money('PEN')
                                    ->visible(fn ($record) => ($record->igv ?? 0) > 0),
                                TextEntry::make('total')
                                    ->label('Total del Item')
                                    ->money('PEN')
                                    ->weight('bold')
                                    ->size('lg'),
                            ]),
                        ])
                        ->columns(1),
                ])
                ->visible(fn ($record) => {
                    // Cargar items si no están cargados
                    if (!$record->relationLoaded('items')) {
                        $record->load('items');
                    }
                    return $record->items && $record->items->count() > 0;
                }),
        ];
    }
}
