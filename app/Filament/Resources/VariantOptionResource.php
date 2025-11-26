<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VariantOptionResource\Pages;
use App\Filament\Resources\VariantOptionResource\RelationManagers;
use App\Models\VariantOption;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class VariantOptionResource extends Resource
{
    protected static ?string $model = VariantOption::class;

    protected static ?string $navigationIcon = 'heroicon-o-swatch';


    protected static ?string $navigationGroup = 'Configuración General';

    protected static ?string $navigationLabel = 'Variantes de Producto'; // Nombre en el menú de navegación
    protected static ?string $slug = 'product-variant-options'; // Ruta del recurso

    protected static ?string $label = 'Opción de Producto'; // Nombre en singular
    protected static ?string $pluralLabel = 'Opciones de Producto'; // Nombre en plural

    protected static ?int $navigationSort = 23;



    public static function form(Form $form): Form
    {
        return $form
            ->schema([

                Forms\Components\TextInput::make('name')
                    ->label('Nombre')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),

                Forms\Components\Select::make('product_option_type_id')
                    ->label('Tipo de Opción de Producto')
                    ->relationship('productOptionType', 'name')
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(function (callable $set, $state) {
                        $type = \App\Models\ProductOptionType::find($state);
                        if ($type) {
                            $set('is_color', $type->is_color);
                        } else {
                            $set('is_color', false);
                        }
                    }),

                Forms\Components\Hidden::make('is_color'),

                Forms\Components\TextInput::make('value')
                    ->label('Valor')
                    ->required()
                    ->visible(fn(callable $get) => !$get('is_color')),

                Forms\Components\ColorPicker::make('value')
                    ->label('Color')
                    ->required()
                    ->visible(fn(callable $get) => $get('is_color')),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('value')
                    ->label('Valor')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\BooleanColumn::make('productOptionType.is_color')
                    ->label('Es Color')

                    ->sortable(),


                Tables\Columns\TextColumn::make('productOptionType.name')
                    ->label('Tipo de Opción')
                    ->searchable()
                    ->sortable(),
            ])
            ->defaultSort('id', 'desc')
            ->filters([

                // Puedes agregar filtros personalizados aquí
                Tables\Filters\SelectFilter::make('product_option_type_id')
                    ->label('Tipo de Opción de Producto')
                    ->relationship('productOptionType', 'name')
                    ->multiple()
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
            'index' => Pages\ListVariantOptions::route('/'),
            'create' => Pages\CreateVariantOption::route('/create'),
            'edit' => Pages\EditVariantOption::route('/{record}/edit'),
        ];
    }
}
