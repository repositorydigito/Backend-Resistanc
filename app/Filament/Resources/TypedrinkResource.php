<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TypedrinkResource\Pages;
use App\Filament\Resources\TypedrinkResource\RelationManagers;
use App\Models\Typedrink;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TypedrinkResource extends Resource
{
    protected static ?string $model = Typedrink::class;

    // protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';


    protected static ?string $navigationGroup = 'Bebidas';

    protected static ?string $navigationLabel = 'Tipo de bebidas'; // Nombre del grupo de navegación

    protected static ?string $label = 'Tipo de Bebida'; // Nombre en singular
    protected static ?string $pluralLabel = 'Tipos de Bebidas'; // Nombre en plural

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
            Section::make('Información del tipo de bebida')
                    ->columns(2)
                    ->schema([

                        Forms\Components\FileUpload::make('image_url')
                            ->label('Imagen')
                            ->disk('public')
                            ->directory('typedrinks')
                            ->visibility('public')
                            ->acceptedFileTypes(['image/*'])
                            ->maxSize(1024 * 5) // 5 MB
                            ->imageResizeMode('crop')
                            ->imageResizeTargetWidth(800)
                            ->imageResizeTargetHeight(600)
                            ->image(),
                        Forms\Components\FileUpload::make('ico_url')
                            ->label('Icono')
                            ->disk('public')
                            ->directory('typedrinks/icons')
                            ->visibility('public')
                            ->acceptedFileTypes(['image/*'])
                            ->maxSize(1024 * 2) // 2 MB
                            ->imageResizeMode('crop')
                            ->imageResizeTargetWidth(100)
                            ->imageResizeTargetHeight(100)
                            ->image(),
                        Forms\Components\TextInput::make('price')
                            ->label('Precio')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->maxValue(9999)
                            ->default(0)
                            ->step(0.01),
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(255),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\ImageColumn::make('image_url'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
            'index' => Pages\ListTypedrinks::route('/'),
            'create' => Pages\CreateTypedrink::route('/create'),
            'edit' => Pages\EditTypedrink::route('/{record}/edit'),
        ];
    }
}
