<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductOptionTypeResource\Pages;
use App\Filament\Resources\ProductOptionTypeResource\RelationManagers;
use App\Models\ProductOptionType;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProductOptionTypeResource extends Resource
{
    protected static ?string $model = ProductOptionType::class;

    protected static ?string $navigationIcon = 'heroicon-o-list-bullet';

    protected static ?string $navigationGroup = 'Tienda';

    protected static ?string $navigationLabel = 'Tipos de Opción de Producto'; // Nombre en el menú de navegación
    protected static ?string $slug = 'product-option-types'; // Ruta del recurso

    protected static ?string $label = 'Tipo de Opción de Producto'; // Nombre en singular
    protected static ?string $pluralLabel = 'Tipos de Opción de Producto'; // Nombre en plural

    // protected static ?int $navigationSort = 2;


    protected static bool $shouldRegisterNavigation = false; // Oculta del menú

    // Opcional: bloquea el acceso por URL
    public static function canAccess(): bool
    {
        return false;
    }


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Información Básica')
                    ->description('Define el tipo de opción de producto, como Talla, Color, etc.')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('slug')
                            ->label('Slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(ProductOptionType::class, 'slug', fn($record) => $record),
                        Forms\Components\Toggle::make('is_color')
                            ->label('Es Color')
                            ->required(),
                    ]),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable(),
                Tables\Columns\TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable(),
                Tables\Columns\BooleanColumn::make('is_color')
                    ->label('Es Color')

                    ->sortable(),

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
            'index' => Pages\ListProductOptionTypes::route('/'),
            'create' => Pages\CreateProductOptionType::route('/create'),
            'edit' => Pages\EditProductOptionType::route('/{record}/edit'),
        ];
    }
}
