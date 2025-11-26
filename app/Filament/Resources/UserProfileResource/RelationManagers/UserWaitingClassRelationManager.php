<?php

namespace App\Filament\Resources\UserProfileResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UserWaitingClassRelationManager extends RelationManager
{
    protected static string $relationship = 'waitingClasses';

    protected static ?string $title = 'Lista de espera';

    protected static ?string $modelLabel = 'Lista de espera'; // Nombre en singular
    protected static ?string $pluralModelLabel = 'Listas de espera'; // Nombre en plural


    public function form(Form $form): Form
    {
        return $form
            ->schema([

                Forms\Components\Select::make('class_schedules_id')
                    ->label('Clase')
                    ->options(function () {
                        return \App\Models\ClassSchedule::with('class')
                            ->where('scheduled_date', '>=', now())
                            ->where('status', 'scheduled')
                            ->get()
                            ->mapWithKeys(fn($schedule) => [$schedule->id => $schedule->class?->name . ' - ' . $schedule->scheduled_date->format('d/m/Y '. $schedule->start_time . ' - ' . $schedule->end_time)])
                            ->toArray();
                    })


                    ->searchable()
                    ->required(),

                Forms\Components\Select::make('user_id')
                    ->label('Usuario')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),

                Forms\Components\Select::make('user_package_id')
                    ->relationship('userPackage', 'package_id')
                    ->nullable(),
                Forms\Components\Select::make('status')
                    ->options([
                        'waiting' => 'En espera',
                        'notified' => 'Notificado',
                        'confirmed' => 'Confirmado',
                        'expired' => 'Expirado',
                        'cancelled' => 'Cancelado',
                    ])
                    ->default('waiting')
                    ->required(),
            ])->columns([
                'sm' => 2,
            ]);
    }
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('user_id')
            ->columns([
                Tables\Columns\TextColumn::make('classSchedule.class.name')
                    ->label('Clase')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Usuario')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('userPackage.package.name')
                    ->label('Paquete')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'waiting' => 'En espera',
                        'notified' => 'Notificado',
                        'confirmed' => 'Confirmado',
                        'expired' => 'Expirado',
                        'cancelled' => 'Cancelado',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'waiting' => 'warning',
                        'notified' => 'info',
                        'confirmed' => 'success',
                        'expired' => 'danger',
                        'cancelled' => 'secondary',
                        default => 'primary',
                    })
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                // Tables\Actions\CreateAction::make(),
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
