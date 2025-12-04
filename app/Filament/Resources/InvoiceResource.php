<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvoiceResource\Pages;
use App\Filament\Resources\InvoiceResource\RelationManagers;
use App\Models\Invoice;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document';


    // protected static ?string $navigationGroup = 'Entrenamiento';

    protected static ?string $navigationLabel = 'Facturación';

    protected static ?string $label = 'Facturación'; // Nombre en singular
    protected static ?string $pluralLabel = 'Facturaciones'; // Nombre en plural

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Datos del Cliente')
                    ->schema([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('cliente_denominacion')->label('Cliente')->required()->maxLength(255),
                            Forms\Components\TextInput::make('cliente_tipo_de_documento')->label('Tipo de documento')->required()->numeric(),
                            Forms\Components\TextInput::make('cliente_numero_de_documento')->label('N° Documento')->required()->maxLength(255),
                            Forms\Components\TextInput::make('cliente_direccion')->label('Dirección')->maxLength(255),
                            Forms\Components\TextInput::make('cliente_email')->label('Email')->email()->maxLength(255),
                            Forms\Components\TextInput::make('cliente_email_1')->label('Email 1')->email()->maxLength(255),
                            Forms\Components\TextInput::make('cliente_email_2')->label('Email 2')->email()->maxLength(255),
                        ]),
                    ]),
                Forms\Components\Section::make('Comprobante')
                    ->schema([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('operacion')->label('Operación')->maxLength(255),
                            Forms\Components\TextInput::make('tipo_de_comprobante')->label('Tipo de comprobante')->required()->numeric(),
                            Forms\Components\TextInput::make('serie')->label('Serie')->required()->maxLength(255),
                            Forms\Components\TextInput::make('numero')->label('Número')->required()->numeric(),
                            Forms\Components\TextInput::make('sunat_transaction')->label('SUNAT Transaction')->numeric(),
                            Forms\Components\DatePicker::make('fecha_de_emision')->label('Fecha de emisión')->required(),
                            Forms\Components\DatePicker::make('fecha_de_vencimiento')->label('Fecha de vencimiento'),
                            Forms\Components\TextInput::make('moneda')->label('Moneda')->required()->numeric(),
                            Forms\Components\TextInput::make('tipo_de_cambio')->label('Tipo de cambio')->numeric(),
                        ]),
                    ]),
                Forms\Components\Section::make('Totales')
                    ->schema([
                        Forms\Components\Grid::make(3)->schema([
                            Forms\Components\TextInput::make('total_gravada')->label('Total Gravada')->numeric(),
                            Forms\Components\TextInput::make('total_igv')->label('Total IGV')->numeric(),
                            Forms\Components\TextInput::make('total')->label('Total')->required()->numeric(),
                            Forms\Components\TextInput::make('total_inafecta')->label('Total Inafecta')->numeric(),
                            Forms\Components\TextInput::make('total_exonerada')->label('Total Exonerada')->numeric(),
                            Forms\Components\TextInput::make('total_gratuita')->label('Total Gratuita')->numeric(),
                            Forms\Components\TextInput::make('total_otros_cargos')->label('Total Otros Cargos')->numeric(),
                            Forms\Components\TextInput::make('descuento_global')->label('Descuento Global')->numeric(),
                            Forms\Components\TextInput::make('total_descuento')->label('Total Descuento')->numeric(),
                            Forms\Components\TextInput::make('total_anticipo')->label('Total Anticipo')->numeric(),
                            Forms\Components\Toggle::make('detraccion')->label('¿Detracción?')->required(),
                        ]),
                    ]),
                Forms\Components\Section::make('Estado y Envío')
                    ->schema([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('envio_estado')->label('Estado de envío')->required()->maxLength(255)->default('pendiente'),
                            Forms\Components\Toggle::make('enviada_a_nubefact')->label('¿Enviada a Nubefact?')->required(),
                            Forms\Components\Toggle::make('aceptada_por_sunat')->label('¿Aceptada por SUNAT?'),
                            Forms\Components\TextInput::make('sunat_description')->label('Descripción SUNAT')->maxLength(255),
                            Forms\Components\TextInput::make('sunat_note')->label('Nota SUNAT')->maxLength(255),
                            Forms\Components\TextInput::make('sunat_responsecode')->label('SUNAT Response Code')->maxLength(255),
                            Forms\Components\TextInput::make('sunat_soap_error')->label('SUNAT SOAP Error')->maxLength(255),
                            Forms\Components\Textarea::make('error_envio')->label('Error de envío')->columnSpanFull(),
                        ]),
                    ]),
                Forms\Components\Section::make('Archivos y Códigos')
                    ->schema([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('enlace')->label('Enlace')->maxLength(255),
                            Forms\Components\TextInput::make('enlace_del_pdf')->label('PDF')->maxLength(255),
                            Forms\Components\TextInput::make('enlace_del_xml')->label('XML')->maxLength(255),
                            Forms\Components\TextInput::make('enlace_del_cdr')->label('CDR')->maxLength(255),
                            Forms\Components\TextInput::make('codigo_hash')->label('Código Hash')->maxLength(255),
                            Forms\Components\Textarea::make('cadena_para_codigo_qr')->label('Cadena para QR')->columnSpanFull(),
                        ]),
                    ]),
                Forms\Components\Section::make('Detalle y Observaciones')
                    ->schema([
                        Forms\Components\Textarea::make('observaciones')->label('Observaciones')->columnSpanFull(),
                        Forms\Components\TextInput::make('guias')->label('Guías'),
                        Forms\Components\TextInput::make('venta_al_credito')->label('Venta al Crédito'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('fecha_de_emision')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('tipo_comprobante_nombre')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn ($record) => $record->isFactura() ? 'info' : 'success')
                    ->sortable()
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where(function ($q) use ($search) {
                            if (stripos($search, 'factura') !== false) {
                                $q->where('tipo_de_comprobante', Invoice::TIPO_FACTURA);
                            } elseif (stripos($search, 'boleta') !== false) {
                                $q->where('tipo_de_comprobante', Invoice::TIPO_BOLETA);
                            }
                        });
                    }),
                Tables\Columns\TextColumn::make('serie')
                    ->label('Serie')
                    ->searchable(),
                Tables\Columns\TextColumn::make('numero')
                    ->label('Número')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('cliente_tipo_documento_nombre')
                    ->label('Tipo Doc.')
                    ->badge()
                    ->color('gray')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('cliente_numero_de_documento')
                    ->label('N° Doc.')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('cliente_denominacion')
                    ->label('Cliente')
                    ->searchable()
                    ->limit(30),
                Tables\Columns\TextColumn::make('total')
                    ->label('Total')
                    ->money('PEN')
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('envio_estado')
                    ->label('Estado de envío')
                    ->colors([
                        'success' => 'enviada',
                        'danger' => 'fallida',
                        'warning' => 'pendiente',
                    ])
                    ->sortable(),
                Tables\Columns\IconColumn::make('enviada_a_nubefact')
                    ->label('¿Enviada?')
                    ->boolean(),
                Tables\Columns\IconColumn::make('aceptada_por_sunat')
                    ->label('Aceptada SUNAT')
                    ->boolean(),
                Tables\Columns\TextColumn::make('error_envio')
                    ->label('Error de envío')
                    ->limit(60)
                    ->toggleable()
                    ->visible(fn($record) => !empty($record->error_envio)),
                Tables\Columns\TextColumn::make('enlace_del_pdf')
                    ->label('PDF')
                    ->url(fn($record) => $record->enlace_del_pdf, true)
                    ->openUrlInNewTab()
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('primary')
                    ->visible(fn($record) => !empty($record->enlace_del_pdf)),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                // Puedes agregar filtros por estado, fecha, etc.
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvoices::route('/'),
            'view' => Pages\ViewInvoice::route('/{record}'),
        ];
    }
}
