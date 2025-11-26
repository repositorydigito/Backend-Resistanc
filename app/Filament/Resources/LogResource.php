<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LogResource\Pages;
use App\Filament\Resources\LogResource\RelationManagers;
use App\Models\Log;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LogResource extends Resource
{
    protected static ?string $model = Log::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Sistema';

    protected static ?string $navigationLabel = 'Logs';

    protected static ?string $slug = 'logs';

    protected static ?string $label = 'Log';

    protected static ?string $pluralLabel = 'Logs';

    protected static ?int $navigationSort = 99;

    // Deshabilitar creación y edición de logs (solo lectura)
    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Sección de Información Principal
                Forms\Components\Section::make('Información del Log')
                    ->description('Detalles principales del registro de actividad')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        Forms\Components\TextInput::make('user_id')
                            ->numeric()
                            ->label('ID Usuario')
                            ->disabled()
                            ->placeholder('ID del usuario que generó el log'),

                        Forms\Components\Placeholder::make('user_name')
                            ->label('Usuario')
                            ->content(function ($record) {
                                return $record?->user?->name ?? 'N/A';
                            }),

                        Forms\Components\TextInput::make('action')
                            ->maxLength(255)
                            ->label('Acción')
                            ->disabled()
                            ->placeholder('Descripción corta de la acción'),
                    ]),

                // Sección de Contenido
                Forms\Components\Section::make('Contenido Detallado')
                    ->description('Información extendida del log')
                    ->icon('heroicon-o-document')
                    ->schema([
                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->label('Descripción')
                            ->disabled()
                            ->placeholder('Descripción detallada de lo ocurrido')
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('data')
                            ->rows(6)
                            ->label('Datos Adicionales')
                            ->disabled()
                            ->placeholder('Información adicional en formato JSON u otro')
                            ->helperText('Generalmente contiene datos técnicos o payloads')
                            ->columnSpanFull(),
                    ]),

                // Sección de Metadatos
                Forms\Components\Section::make('Metadatos')
                    ->description('Información automática del sistema')
                    ->icon('heroicon-o-clock')
                    ->collapsible()
                    ->schema([
                        Forms\Components\DateTimePicker::make('created_at')
                            ->label('Fecha de Creación')
                            ->disabled()
                            ->displayFormat('d/m/Y H:i:s'),

                        Forms\Components\DateTimePicker::make('updated_at')
                            ->label('Última Actualización')
                            ->disabled()
                            ->displayFormat('d/m/Y H:i:s'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user_id')
                    ->numeric()
                    ->sortable()
                    ->label('ID Usuario')
                    ->searchable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->numeric()
                    ->sortable()
                    ->label('Usuario')
                    ->searchable(),


                Tables\Columns\TextColumn::make('action')
                    ->searchable()
                    ->label('Acción')
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 50 ? $state : null;
                    }),

                Tables\Columns\TextColumn::make('description')
                    ->searchable()
                    ->label('Descripción')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 30 ? $state : null;
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable()
                    ->label('Fecha/Hora')
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable()
                    ->label('Actualizado')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // Filtros útiles para logs
                Tables\Filters\Filter::make('has_user')
                    ->label('Con Usuario')
                    ->query(fn(Builder $query): Builder => $query->whereNotNull('user_id')),

                Tables\Filters\Filter::make('no_user')
                    ->label('Sin Usuario')
                    ->query(fn(Builder $query): Builder => $query->whereNull('user_id')),

                Tables\Filters\SelectFilter::make('user_id')
                    ->label('Usuario Específico')
                    ->relationship('user', 'name') // Asumiendo que tienes relación con User
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Ver'),
            ])
            ->bulkActions([
                // No permitir acciones masivas en logs
            ])
            ->defaultSort('created_at', 'desc') // Ordenar por más reciente primero
            ->emptyStateHeading('No hay logs registrados')
            ->emptyStateDescription('Cuando se generen logs, aparecerán aquí.');
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
            'index' => Pages\ListLogs::route('/'),
            'view' => Pages\ViewLog::route('/{record}'),
        ];
    }
}
