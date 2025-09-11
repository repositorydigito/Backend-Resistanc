<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductTagResource\Pages;
use App\Filament\Resources\ProductTagResource\RelationManagers;
use App\Models\ProductTag;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProductTagResource extends Resource
{
    protected static ?string $model = ProductTag::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag'; // Icono del menú de navegación

    protected static ?string $navigationGroup = 'Tienda';

    protected static ?string $navigationLabel = 'Etiquetas'; // Nombre en el menú de navegación
    protected static ?string $slug = 'product-tags'; // Ruta del recurso

    protected static ?string $label = 'Etiqueta'; // Nombre en singular
    protected static ?string $pluralLabel = 'Etiquetas'; // Nombre en plural

    // protected static ?int $navigationSort = 2;


    protected static bool $shouldRegisterNavigation = false; // Oculta del menú

    // protected static ?int $navigationSort = 5;


    // Opcional: bloquea el acceso por URL
    public static function canAccess(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Información de la Etiqueta')
                    ->columns(1)
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre de la Etiqueta')
                            ->unique(ignoreRecord: true)
                            ->required()
                            ->maxLength(40),
                    ]),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre de la Etiqueta')
                    ->sortable()
                    ->searchable(),
                // Tables\Columns\TextColumn::make('created_at')
                //     ->dateTime()
                //     ->sortable()
                //     ->toggleable(isToggledHiddenByDefault: true),
                // Tables\Columns\TextColumn::make('updated_at')
                //     ->dateTime()
                //     ->sortable()
                //     ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProductTags::route('/'),
            'create' => Pages\CreateProductTag::route('/create'),
            'edit' => Pages\EditProductTag::route('/{record}/edit'),
        ];
    }
}
