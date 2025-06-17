<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductCategoryResource\Pages;
use App\Filament\Resources\ProductCategoryResource\RelationManagers;
use App\Models\ProductCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProductCategoryResource extends Resource
{
    protected static ?string $model = ProductCategory::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2'; // Icono del menú de navegación



    protected static ?string $navigationGroup = 'Tienda';

    protected static ?string $navigationLabel = 'Categorías'; // Nombre en el menú de navegación
    protected static ?string $slug = 'product-categories'; // Ruta del recurso

    protected static ?string $label = 'Categoría'; // Nombre en singular
    protected static ?string $pluralLabel = 'Categorías'; // Nombre en plural

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Toggle::make('is_active')
                    ->label('¿Está Activa?')
                    ->default(true)
                    ->required(),
                Forms\Components\Section::make('Información de la Categoría')
                    ->columns(2)
                    ->schema([
                        Forms\Components\FileUpload::make('image_url')
                            ->label('Imagen')
                            ->disk('public')
                            ->directory('drinks')
                            ->visibility('public')
                            ->acceptedFileTypes(['image/*'])
                            ->maxSize(1024 * 5) // 5 MB
                            ->imageResizeMode('crop')
                            ->imageResizeTargetWidth(800)
                            ->imageResizeTargetHeight(600)
                            ->image()
                            ->columnSpanFull()
                            ->required(),
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre de la Categoría')
                            ->unique(
                                ignoreRecord: true,

                            )
                            ->required()
                            ->maxLength(100),


                        Forms\Components\TextInput::make('sort_order')
                            ->label('Orden de Clasificación')
                            ->unique(ignoreRecord: true)
                            ->required()
                            ->numeric()
                            ->helperText(function () {
                                $existingOrders = ProductCategory::orderBy('sort_order')
                                    ->pluck('sort_order')
                                    ->filter()
                                    ->unique()
                                    ->values()
                                    ->toArray();

                                $nextAvailable = (count($existingOrders) > 0 ? max($existingOrders) : 0) + 1;

                                return "Órdenes ya usados: " . (count($existingOrders) > 0 ? implode(', ', $existingOrders) : 'Ninguno') .
                                    ". Siguiente disponible: {$nextAvailable}";
                            })
                            ->default(function () {
                                return (ProductCategory::max('sort_order') ?? 0) + 1;
                            }),


                        Forms\Components\Select::make('parent_id')
                            ->label('Categoría Padre')
                            ->preload()
                            ->options(
                                ProductCategory::whereNull('parent_id')
                                    ->pluck('name', 'id')
                            )
                            ->placeholder('Selecciona una categoría padre')
                            ->default(null)
                            ->columnSpanFull()
                            ->required()
                            ->relationship('parent', 'name')
                            ->searchable(),


                        Forms\Components\Textarea::make('description')
                            ->label('Descripción')
                            ->columnSpanFull(),


                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre de la Categoría')
                    ->searchable(),
                // Tables\Columns\TextColumn::make('slug')
                //     ->searchable(),
                Tables\Columns\TextColumn::make('parent.name')
                    ->label('Categoría Padre')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\ImageColumn::make('image_url'),
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Orden de Clasificación')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('¿Está Activa?')
                    ->boolean(),

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
            'index' => Pages\ListProductCategories::route('/'),
            'create' => Pages\CreateProductCategory::route('/create'),
            'edit' => Pages\EditProductCategory::route('/{record}/edit'),
        ];
    }
}
