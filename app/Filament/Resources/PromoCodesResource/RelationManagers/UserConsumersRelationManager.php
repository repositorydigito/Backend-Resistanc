<?php

namespace App\Filament\Resources\PromoCodesResource\RelationManagers;

use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UserConsumersRelationManager extends RelationManager
{
    protected static string $relationship = 'users';

    protected static ?string $title = 'Usuarios que Consumieron';

    protected static ?string $modelLabel = 'Usuario Consumidor';

    protected static ?string $pluralModelLabel = 'Usuarios Consumidores';

    protected static ?string $recordTitleAttribute = 'name';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información del Consumo')
                    ->description('Los consumos se registran a través de la API')
                    ->schema([
                        Forms\Components\Placeholder::make('info')
                            ->label('Información')
                            ->content('Los consumos de códigos promocionales se registran automáticamente a través de la API cuando los usuarios utilizan los códigos en la aplicación.')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Usuario')
                    ->getStateUsing(fn ($record) => $record->name ?? 'N/A')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->getStateUsing(fn ($record) => $record->email ?? 'N/A')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('userProfile.phone')
                    ->label('Teléfono')
                    ->getStateUsing(fn ($record) => $record->userProfile->phone ?? 'Sin teléfono')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('pivot.monto')
                    ->label('Monto Consumido')
                    ->money('PEN')
                    ->sortable()
                    ->color('success'),

                Tables\Columns\TextColumn::make('pivot.notes')
                    ->label('Notas')
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 50 ? $state : null;
                    }),

                Tables\Columns\TextColumn::make('pivot.created_at')
                    ->label('Fecha de Consumo')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('pivot.updated_at')
                    ->label('Última Actualización')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado del Usuario')
                    ->options([
                        'active' => 'Activo',
                        'inactive' => 'Inactivo',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['value'],
                            fn (Builder $query, $status): Builder => $query->where('status', $status),
                        );
                    }),
            ])
            ->headerActions([
                // Sin acciones de creación - se maneja por API
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Ver Detalles')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->modalHeading('Detalles del Consumo')
                    ->modalDescription('Información completa del consumo del código promocional'),
            ])
            ->bulkActions([
                // Sin acciones masivas - solo lectura
            ])
            ->emptyStateHeading('Sin consumos registrados')
            ->emptyStateDescription('Los consumos se registran automáticamente cuando los usuarios utilizan este código promocional a través de la aplicación')
            ->emptyStateIcon('heroicon-o-users')
            ->defaultSort('pivot.created_at', 'desc');
    }
}
