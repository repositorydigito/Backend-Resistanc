<?php

namespace App\Filament\Resources;

use App\Filament\Resources\JuiceOrderResource\Pages;
use App\Filament\Resources\JuiceOrderResource\RelationManagers;
use App\Models\JuiceOrder;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class JuiceOrderResource extends Resource
{
    protected static ?string $model = JuiceOrder::class;

    // protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Gestión de Shakes'; // Nombre del grupo de navegación

    protected static ?string $navigationLabel = 'Historial de pedidos'; // Nombre del grupo de navegación

    protected static ?string $label = 'Historial de pedido'; // Nombre en singular
    protected static ?string $pluralLabel = 'Historial de pedidos'; // Nombre en plural

    protected static ?int $navigationSort = 13;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información del Pedido')
                    ->schema([
                        Forms\Components\TextInput::make('order_number')
                            ->label('Número de Orden')
                            ->disabled()
                            ->maxLength(255),

                        Forms\Components\Select::make('user_id')
                            ->label('Usuario')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->required()
                            ->disabled(),

                        Forms\Components\TextInput::make('user_name')
                            ->label('Nombre del Usuario')
                            ->disabled()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('user_email')
                            ->label('Email del Usuario')
                            ->disabled()
                            ->email()
                            ->maxLength(255),
                    ])->columns(2),

                Forms\Components\Section::make('Estado del Pedido')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Estado')
                            ->options([
                                'pending' => 'Pendiente',
                                'confirmed' => 'Confirmado',
                                'preparing' => 'Preparando',
                                'ready' => 'Listo',
                                'delivered' => 'Entregado',
                                'cancelled' => 'Cancelado',
                            ])
                            ->required(),
                    ])->columns(1),

                Forms\Components\Section::make('Montos')
                    ->schema([
                        Forms\Components\TextInput::make('total_amount_soles')
                            ->label('Total a Pagar (S/)')
                            ->numeric()
                            ->disabled()
                            ->prefix('S/')
                            ->extraAttributes(['class' => 'font-bold text-xl']),
                    ])->columns(1),

                Forms\Components\Section::make('Información Adicional')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Notas del Pedido')
                            ->rows(2),
                    ])->columns(1),

                Forms\Components\Section::make('Fechas del Pedido')
                    ->schema([
                        Forms\Components\DateTimePicker::make('created_at')
                            ->label('Pedido Creado')
                            ->disabled(),

                        Forms\Components\DateTimePicker::make('delivered_at')
                            ->label('Entregado en')
                            ->disabled(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order_number')
                    ->label('N° Orden')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Usuario')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('user_email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Estado')
                    ->colors([
                        'warning' => 'pending',
                        'info' => 'confirmed',
                        'primary' => 'preparing',
                        'success' => 'ready',
                        'success' => 'delivered',
                        'danger' => 'cancelled',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Pendiente',
                        'confirmed' => 'Confirmado',
                        'preparing' => 'Preparando',
                        'ready' => 'Listo',
                        'delivered' => 'Entregado',
                        'cancelled' => 'Cancelado',
                        default => $state,
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_amount_soles')
                    ->label('Total')
                    ->money('PEN')
                    ->sortable()
                    ->weight('bold')
                    ->color('success'),

                Tables\Columns\TextColumn::make('details_count')
                    ->label('Bebidas')
                    ->counts('details')
                    ->sortable()
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Pedido Creado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('delivered_at')
                    ->label('Entregado en')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->color('success')
                    ->placeholder('Pendiente'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado del Pedido')
                    ->options([
                        'pending' => 'Pendiente',
                        'confirmed' => 'Confirmado',
                        'preparing' => 'Preparando',
                        'ready' => 'Listo',
                        'delivered' => 'Entregado',
                        'cancelled' => 'Cancelado',
                    ]),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Desde'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Hasta'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('mark_delivered')
                    ->label('Marcar como Entregado')
                    ->icon('heroicon-o-check-circle')
                    ->action(function (JuiceOrder $record): void {
                        $record->updateStatus('delivered');
                    })
                    ->requiresConfirmation()
                    ->color('success')
                    ->visible(fn (JuiceOrder $record): bool => $record->status !== 'delivered'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\DetailsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListJuiceOrders::route('/'),
            'view' => Pages\ViewJuiceOrder::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false; // No permitir crear órdenes desde el admin (solo desde la app)
    }
}
