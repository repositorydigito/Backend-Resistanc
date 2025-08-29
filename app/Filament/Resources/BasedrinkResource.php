<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BasedrinkResource\Pages;
use App\Filament\Resources\BasedrinkResource\RelationManagers;
use App\Models\Basedrink;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BasedrinkResource extends Resource
{
    protected static ?string $model = Basedrink::class;

    protected static ?string $navigationGroup = 'Bebidas';

    protected static ?string $navigationLabel = 'Bebidas base'; // Nombre del grupo de navegación

    protected static ?string $label = 'Bebida base'; // Nombre en singular
    protected static ?string $pluralLabel = 'Bebidas base'; // Nombre en plural

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Toggle::make('is_active')
                    ->label('¿Está activa?')
                    ->default(true),
                Section::make('Información de la bebida base')
                    ->columns(1)
                    ->schema([



                        Forms\Components\FileUpload::make('image_url')
                            ->label('Imagen')
                            ->disk('public')
                            ->directory('basedrinks')
                            ->visibility('public')
                            ->acceptedFileTypes(['image/*'])
                            ->maxSize(1024 * 5) // 5 MB
                            ->imageResizeMode('crop')
                            ->imageResizeTargetWidth(800)
                            ->imageResizeTargetHeight(600)
                            ->image(),
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
                    ->label('Nombre')
                    ->searchable(),
                // Tables\Columns\ImageColumn::make('image_url'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('¿Está activa?')
                    ->boolean(),
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
            'index' => Pages\ListBasedrinks::route('/'),
            'create' => Pages\CreateBasedrink::route('/create'),
            'edit' => Pages\EditBasedrink::route('/{record}/edit'),
        ];
    }
}
