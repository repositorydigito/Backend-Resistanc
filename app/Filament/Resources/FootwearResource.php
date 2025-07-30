<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FootwearResource\Pages;
use App\Filament\Resources\FootwearResource\RelationManagers;
use App\Models\Footwear;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class FootwearResource extends Resource
{
    protected static ?string $model = Footwear::class;

    protected static ?string $navigationIcon = 'heroicon-o-square-3-stack-3d';

    protected static ?string $navigationGroup = 'Administración'; // Nombre del grupo de navegación

    protected static ?string $navigationLabel = 'Calzado'; // Nombre del grupo de navegación

    protected static ?string $label = 'Calzado'; // Nombre en singular
    protected static ?string $pluralLabel = 'Calzados'; // Nombre en plural

    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información Básica')
                    ->schema([
                        // Forms\Components\TextInput::make('code')
                        //     ->required()
                        //     ->maxLength(255)
                        //     ->label('Código'),

                        Forms\Components\TextInput::make('model')
                            ->maxLength(255)
                            ->label('Modelo'),

                        Forms\Components\TextInput::make('brand')
                            ->maxLength(255)
                            ->label('Marca'),
                        Forms\Components\Select::make('status')
                            ->options([
                                'available' => 'Disponible',
                                'out_of_stock' => 'Agotado',
                                'maintenance' => 'En mantenimiento',
                                'in_use' => 'En uso'
                            ])
                            ->default('available')
                            ->label('Estado'),

                        // Forms\Components\Select::make('type')
                        //     ->options([
                        //         'sneakers' => 'Tenis',
                        //         'boots' => 'Botas',
                        //         'sandals' => 'Sandalias',
                        //         'formal' => 'Formal'
                        //     ])
                        //     ->label('Tipo'),

                        Forms\Components\Select::make('gender')
                            ->options([
                                'male' => 'Masculino',
                                'female' => 'Femenino',
                                'unisex' => 'Unisex'
                            ])
                            ->label('Género'),
                    ])->columns(2),

                Forms\Components\Section::make('Detalles')
                    ->schema([
                        Forms\Components\Select::make('size')
                            ->label('Talla')
                            ->options([
                                34 => '34',
                                35 => '35',
                                36 => '36',
                                37 => '37',
                                38 => '38',
                                39 => '39',
                                40 => '40',
                                41 => '41',
                                42 => '42',
                                43 => '43',
                                44 => '44',
                                45 => '45',
                                46 => '46',
                                47 => '47',
                            ])
                            ->searchable() // Opcional: permite buscar la talla
                            ->required(),

                        Forms\Components\Select::make('color')
                            ->label('Color')
                            ->searchable()
                            ->options([
                                'Negro' => 'Negro',
                                'Blanco' => 'Blanco',
                                'Azul' => 'Azul',
                                'Rojo' => 'Rojo',
                                'Verde' => 'Verde',
                                'Amarillo' => 'Amarillo',
                                'Rosado' => 'Rosado',
                                'Gris' => 'Gris',
                                'Marrón' => 'Marrón',
                                'Beige' => 'Beige',
                                'Morado' => 'Morado',
                                'Naranja' => 'Naranja',
                                'Multicolor' => 'Multicolor',
                            ])
                            ->required(),



                        Forms\Components\Textarea::make('description')
                            ->label('Descripción')
                            ->columnSpanFull(),

                        Forms\Components\FileUpload::make('image')
                            ->label('Imagen principal')
                            ->directory('footwear')
                            ->image()
                            ->columnSpanFull(),

                        Forms\Components\FileUpload::make('images_gallery')
                            ->label('Galería de imágenes')
                            ->directory('footwear/gallery')
                            ->image()
                            ->multiple()
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Código')
                    ->searchable(),

                Tables\Columns\TextColumn::make('brand')
                    ->label('Marca')
                    ->searchable(),

                Tables\Columns\TextColumn::make('model')
                    ->label('Modelo')
                    ->searchable(),

                // Tables\Columns\TextColumn::make('type')
                //     ->label('Tipo')
                //     ->formatStateUsing(fn(string $state): string => match ($state) {
                //         'sneakers' => 'Tenis',
                //         'boots' => 'Botas',
                //         'sandals' => 'Sandalias',
                //         'formal' => 'Formal'
                //     })
                //     ->badge()
                //     ->color(fn(string $state): string => match ($state) {
                //         'sneakers' => 'info',
                //         'boots' => 'warning',
                //         'sandals' => 'success',
                //         'formal' => 'gray'
                //     }),

                Tables\Columns\TextColumn::make('size')
                    ->label('Talla')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'available' => 'success',
                        'maintenance' => 'warning',
                        'in_use' => 'primary',
                        'lost' => 'danger'
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'available' => 'Disponible',
                        'maintenance' => 'Mantenimiento',
                        'in_use' => 'En uso',
                        'lost' => 'Perdido'
                    }),

                Tables\Columns\ImageColumn::make('image')
                    ->label('Imagen')
                    ->circular(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipo')
                    ->options([
                        'sneakers' => 'Tenis',
                        'boots' => 'Botas',
                        'sandals' => 'Sandalias',
                        'formal' => 'Formal'
                    ]),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'available' => 'Disponible',
                        'out_of_stock' => 'Agotado',
                        'maintenance' => 'Mantenimiento',
                        'in_use' => 'En uso'
                    ]),

                Tables\Filters\SelectFilter::make('gender')
                    ->label('Género')
                    ->options([
                        'male' => 'Masculino',
                        'female' => 'Femenino',
                        'unisex' => 'Unisex'
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListFootwears::route('/'),
            'create' => Pages\CreateFootwear::route('/create'),
            'edit' => Pages\EditFootwear::route('/{record}/edit'),
        ];
    }
}
