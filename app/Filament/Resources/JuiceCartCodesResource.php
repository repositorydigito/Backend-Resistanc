<?php

namespace App\Filament\Resources;

use App\Filament\Resources\JuiceCartCodesResource\Pages;
use App\Filament\Resources\JuiceCartCodesResource\RelationManagers;
use App\Models\JuiceCartCodes;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class JuiceCartCodesResource extends Resource
{
    protected static ?string $model = JuiceCartCodes::class;

    // protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Bebidas'; // Nombre del grupo de navegación

    protected static ?string $navigationLabel = 'Carrito de Jugos'; // Nombre del grupo de navegación
    protected static ?string $label = 'Carrito de Jugo'; // Nombre en singular
    protected static ?string $pluralLabel = 'Carritos de Jugos'; // Nombre en plural

    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('code')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('user_id')
                    ->required()
                    ->numeric(),
                Forms\Components\Toggle::make('is_used')
                    ->required(),
                Forms\Components\TextInput::make('juice_order_id')
                    ->numeric(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->searchable(),
                Tables\Columns\TextColumn::make('user_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_used')
                    ->boolean(),
                Tables\Columns\TextColumn::make('juice_order_id')
                    ->numeric()
                    ->sortable(),
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
            'index' => Pages\ListJuiceCartCodes::route('/'),
            'create' => Pages\CreateJuiceCartCodes::route('/create'),
            'edit' => Pages\EditJuiceCartCodes::route('/{record}/edit'),
        ];
    }
}
