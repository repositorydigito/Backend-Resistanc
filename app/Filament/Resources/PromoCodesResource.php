<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PromoCodesResource\Pages;
use App\Filament\Resources\PromoCodesResource\RelationManagers;
use App\Models\PromoCodes;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PromoCodesResource extends Resource
{
    protected static ?string $model = PromoCodes::class;

    protected static ?string $navigationIcon = 'heroicon-o-gift';


    protected static ?string $navigationGroup = 'Configuración General'; // Nombre del grupo de navegación

    protected static ?string $navigationLabel = 'Codigos de promocion'; // Nombre del grupo de navegación

    protected static ?string $label = 'Codigos de promocion'; // Nombre en singular
    protected static ?string $pluralLabel = 'Codigos de promocion'; // Nombre en plural

    protected static ?int $navigationSort = 25;


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información Básica')
                    ->description('Datos generales del código de promoción')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre de la Promoción')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Ej: Descuento Verano 2025'),
                        Forms\Components\TextInput::make('name_supplier')
                            ->label('Nombre del Proveedor')
                            ->maxLength(255)
                            ->placeholder('Ej: Proveedor Ejemplo S.A.'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Configuración del Código')
                    ->description('Configura el formato y estado del código')
                    ->schema([
                        Forms\Components\TextInput::make('initial')
                            ->label('Inicial del Código')
                            ->required()
                            ->maxLength(10)
                            ->placeholder('Ej: VER'),
                        Forms\Components\TextInput::make('code')
                            ->label('Código Completo')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Se genera automáticamente')
                            ->disabled()
                            ->visible(fn(string $operation): bool => $operation === 'edit')
                            ->helperText('El código se genera automáticamente al guardar'),
                        Forms\Components\Select::make('type')
                            ->label('Tipo de Promoción')
                            ->required()
                            ->options([
                                'consumption' => 'Por Consumo',
                                'season' => 'Por Temporada',
                            ])
                            ->default('consumption')
                            ->live()
                            ->helperText('Consumo: se aplica al momento de la compra. Temporada: solo válido en fechas específicas'),
                        Forms\Components\Select::make('status')
                            ->label('Estado')
                            ->required()
                            ->options([
                                'active' => 'Activo',
                                'inactive' => 'Inactivo',
                            ])
                            ->default('active'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Vigencia de Temporada')
                    ->description('Configura las fechas de validez para promociones por temporada')
                    ->schema([
                        Forms\Components\DateTimePicker::make('start_date')
                            ->label('Fecha de Inicio')
                            ->native(false)
                            ->displayFormat('d/m/Y H:i')
                            ->seconds(false)
                            ->helperText('Fecha y hora desde cuando la promoción es válida'),
                        Forms\Components\DateTimePicker::make('end_date')
                            ->label('Fecha de Fin')
                            ->native(false)
                            ->displayFormat('d/m/Y H:i')
                            ->seconds(false)
                            ->after('start_date')
                            ->helperText('Fecha y hora hasta cuando la promoción es válida'),
                    ])
                    ->columns(2)
                    ->visible(fn(Forms\Get $get): bool => $get('type') === 'season')
                    ->collapsed(),
            ]);
    }
    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                Tables\Columns\TextColumn::make('id')
                    ->label('Id')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name_supplier')
                    ->label('Proveedor')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('code')
                    ->label('Código')
                    ->searchable()
                    ->copyable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'consumption' => 'info',
                        'season' => 'warning',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'consumption' => 'Consumo',
                        'season' => 'Temporada',
                    }),
                Tables\Columns\TextColumn::make('start_date')
                    ->label('Inicio')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('end_date')
                    ->label('Fin')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'danger',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'active' => 'Activo',
                        'inactive' => 'Inactivo',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado el')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Actualizado el')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipo')
                    ->options([
                        'consumption' => 'Por Consumo',
                        'season' => 'Por Temporada',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'active' => 'Activo',
                        'inactive' => 'Inactivo',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            RelationManagers\PackageDiscountsRelationManager::class,
            RelationManagers\UserConsumersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPromoCodes::route('/'),
            'create' => Pages\CreatePromoCodes::route('/create'),
            'edit' => Pages\EditPromoCodes::route('/{record}/edit'),
        ];
    }
}
