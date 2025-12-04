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
            Section::make('Comprobante')
                ->schema([
                    Grid::make(3)->schema([
                        TextEntry::make('tipo_comprobante_nombre')
                            ->label('Tipo')
                            ->badge()
                            ->color(fn ($record) => $record->isFactura() ? 'info' : 'success'),
                        TextEntry::make('numero_completo')
                            ->label('Número')
                            ->formatStateUsing(fn ($record) => "{$record->serie}-{$record->numero}"),
                        TextEntry::make('fecha_de_emision')
                            ->label('Fecha')
                            ->date('d/m/Y'),
                    ]),
                ]),

            Divider::make(),

            Section::make('Cliente')
                ->schema([
                    Grid::make(2)->schema([
                        TextEntry::make('cliente_denominacion')
                            ->label('Nombre / Razón Social'),
                        TextEntry::make('cliente_tipo_documento_nombre')
                            ->label('Tipo de Documento')
                            ->badge()
                            ->color('gray'),
                        TextEntry::make('cliente_numero_de_documento')
                            ->label('N° Documento'),
                    ]),
                ]),

            Divider::make(),

            Section::make('Totales')
                ->schema([
                    Grid::make(3)->schema([
                        TextEntry::make('total_gravada')
                            ->label('Total Gravada')
                            ->money('PEN')
                            ->visible(fn ($record) => $record->total_gravada > 0),
                        TextEntry::make('total_igv')
                            ->label('Total IGV')
                            ->money('PEN')
                            ->visible(fn ($record) => $record->total_igv > 0),
                        TextEntry::make('total')
                            ->label('Total')
                            ->money('PEN')
                            ->weight('bold'),
                    ]),
                ]),

            Divider::make(),

            Section::make('Estado SUNAT')
                ->schema([
                    Grid::make(2)->schema([
                        BadgeEntry::make('envio_estado')
                            ->label('Estado')
                            ->colors([
                                'success' => 'enviada',
                                'danger' => 'fallida',
                                'warning' => 'pendiente',
                            ]),
                        IconEntry::make('aceptada_por_sunat')
                            ->label('Aceptada por SUNAT')
                            ->boolean(),
                    ]),
                    TextEntry::make('error_envio')
                        ->label('Error')
                        ->color('danger')
                        ->visible(fn ($record) => !empty($record->error_envio)),
                ]),

            Divider::make(),

            Section::make('Archivos')
                ->schema([
                    Grid::make(3)->schema([
                        TextEntry::make('enlace_del_pdf')
                            ->label('PDF')
                            ->url(fn ($record) => $record->enlace_del_pdf, true)
                            ->openUrlInNewTab()
                            ->icon('heroicon-o-document-arrow-down')
                            ->color('danger')
                            ->visible(fn ($record) => !empty($record->enlace_del_pdf)),
                        TextEntry::make('enlace_del_xml')
                            ->label('XML')
                            ->url(fn ($record) => $record->enlace_del_xml, true)
                            ->openUrlInNewTab()
                            ->icon('heroicon-o-document-arrow-down')
                            ->color('info')
                            ->visible(fn ($record) => !empty($record->enlace_del_xml)),
                        TextEntry::make('enlace_del_cdr')
                            ->label('CDR')
                            ->url(fn ($record) => $record->enlace_del_cdr, true)
                            ->openUrlInNewTab()
                            ->icon('heroicon-o-document-arrow-down')
                            ->color('success')
                            ->visible(fn ($record) => !empty($record->enlace_del_cdr)),
                    ]),
                ])
                ->visible(fn ($record) => !empty($record->enlace_del_pdf) || !empty($record->enlace_del_xml) || !empty($record->enlace_del_cdr)),

            Divider::make(),

            Section::make('Items')
                ->schema([
                    RepeatableEntry::make('items')
                        ->schema([
                            Grid::make(4)->schema([
                                TextEntry::make('descripcion')
                                    ->label('Descripción')
                                    ->columnSpan(2),
                                TextEntry::make('cantidad')
                                    ->label('Cantidad')
                                    ->numeric(),
                                TextEntry::make('total')
                                    ->label('Total')
                                    ->money('PEN')
                                    ->weight('bold'),
                            ]),
                        ]),
                ])
                ->visible(fn ($record) => $record->items && count($record->items) > 0),
        ];
    }
}
