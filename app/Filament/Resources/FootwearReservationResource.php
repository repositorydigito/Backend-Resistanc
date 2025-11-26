<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FootwearReservationResource\Pages;
use App\Models\FootwearReservation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class FootwearReservationResource extends Resource
{
    protected static ?string $model = FootwearReservation::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationGroup = 'Gestión de Calzados';
    protected static ?string $navigationLabel = 'Reserva de calzado';
    protected static ?string $label = 'Reserva de calzado';
    protected static ?string $pluralLabel = 'Reservas de calzado';
    protected static ?int $navigationSort = 6;

    public static function form(Form $form): Form
    {
        return $form->schema([]); // No crear ni editar
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID'),
                Tables\Columns\TextColumn::make('footwear.code')->label('Código calzado'),
                Tables\Columns\TextColumn::make('userClient.name')->label('Cliente'),
                Tables\Columns\TextColumn::make('reservation_date')->label('F. Reserva')->dateTime(),
                Tables\Columns\TextColumn::make('scheduled_date')->label('F. Programada')->dateTime(),
                Tables\Columns\TextColumn::make('expiration_date')->label('F. Expiración')->dateTime(),
                Tables\Columns\TextColumn::make('status')->label('Estado')->badge()->color(fn($state) => match($state) {
                    'pending' => 'warning',
                    'confirmed' => 'success',
                    'canceled' => 'danger',
                    'expired' => 'gray',
                }),
            ])
            ->defaultSort('reservation_date', 'desc')
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFootwearReservations::route('/'),
            'view' => Pages\ViewFootwearReservation::route('/{record}'),
        ];
    }
}
