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
                            ->visible(fn (string $operation): bool => $operation === 'edit')
                            ->helperText('El código se genera automáticamente al guardar'),
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
            ]);
    }
 public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable(),
                Tables\Columns\TextColumn::make('name_supplier')
                    ->label('Proveedor')
                    ->searchable(),
                Tables\Columns\TextColumn::make('initial')
                    ->label('Inicial')
                    ->searchable(),
                Tables\Columns\TextColumn::make('code')
                    ->label('Código')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match($state) {
                        'active' => 'success',
                        'inactive' => 'danger',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado el')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Actualizado el')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
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
