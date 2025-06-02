<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use App\Models\UserPaymentMethod;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UserPaymentMethodRelationManager extends RelationManager
{
    protected static string $relationship = 'paymentMethods'; // ✅ Relación directa



    protected static ?string $title = 'Métodos de Pago';

    protected static ?string $modelLabel = 'Método de Pago'; // Nombre en singular
    protected static ?string $pluralModelLabel = 'Métodos de Pago'; // Nombre en plural


    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información del Método de Pago')
                    ->schema([
                        Forms\Components\Select::make('payment_type')
                            ->label('Tipo de Pago')
                            ->options(UserPaymentMethod::PAYMENT_TYPES)
                            ->required(),

                        Forms\Components\Select::make('card_brand')
                            ->label('Marca de Tarjeta')
                            ->options(UserPaymentMethod::CARD_BRANDS)
                            ->required(),

                        Forms\Components\TextInput::make('card_last_four')
                            ->label('Últimos 4 Dígitos')
                            ->maxLength(4)
                            ->required(),

                        Forms\Components\TextInput::make('card_holder_name')
                            ->label('Nombre del Titular')
                            ->required(),

                        Forms\Components\Select::make('card_expiry_month')
                            ->label('Mes de Expiración')
                            ->options(collect(range(1, 12))->mapWithKeys(fn($m) => [$m => sprintf('%02d', $m)]))
                            ->required(),

                        Forms\Components\Select::make('card_expiry_year')
                            ->label('Año de Expiración')
                            ->options(collect(range(date('Y'), date('Y') + 10))->mapWithKeys(fn($y) => [$y => $y]))
                            ->required(),

                        Forms\Components\TextInput::make('bank_name')
                            ->label('Banco'),

                        Forms\Components\Toggle::make('is_default')
                            ->label('Método por Defecto'),

                        Forms\Components\Select::make('status')
                            ->label('Estado')
                            ->options(UserPaymentMethod::STATUSES)
                            ->default('active'),
                    ])
                    ->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('card_brand')
            ->columns([
                Tables\Columns\TextColumn::make('payment_type')
                    ->label('Tipo de Pago')
                    ->formatStateUsing(fn($state) => UserPaymentMethod::PAYMENT_TYPES[$state] ?? $state)
                    ->badge(),

                Tables\Columns\TextColumn::make('card_brand')
                    ->label('Marca de Tarjeta')
                    ->formatStateUsing(fn($state) => UserPaymentMethod::CARD_BRANDS[$state] ?? ucfirst($state))
                    ->badge(),

                Tables\Columns\TextColumn::make('card_last_four')
                    ->label('Últimos 4 Dígitos')
                    ->formatStateUsing(fn($state) => '**** ' . $state),

                Tables\Columns\TextColumn::make('card_holder_name')
                    ->label('Nombre del Titular')
                    ->searchable(),

                Tables\Columns\TextColumn::make('expiry_date')
                    ->label('Fecha de Expiración')
                    ->getStateUsing(fn($record) => $record->expiry_date)
                    ->badge()
                    ->color(fn($record) => $record->is_expired ? 'danger' : 'success'),

                Tables\Columns\IconColumn::make('is_default')
                    ->label('Método Predeterminado')
                    ->boolean(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Estado Actual')
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'active' => 'success',
                        'expired' => 'danger',
                        'blocked' => 'danger',
                        'pending' => 'warning',
                        default => 'gray'
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('payment_type')
                    ->options(UserPaymentMethod::PAYMENT_TYPES),

                Tables\Filters\SelectFilter::make('card_brand')
                    ->options(UserPaymentMethod::CARD_BRANDS),

                Tables\Filters\SelectFilter::make('status')
                    ->options(UserPaymentMethod::STATUSES),

                Tables\Filters\Filter::make('active_only')
                    ->label('Solo activos')
                    ->query(fn($query) => $query->where('status', 'active'))
                    ->default(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
