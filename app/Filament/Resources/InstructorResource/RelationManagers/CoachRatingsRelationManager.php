<?php

namespace App\Filament\Resources\InstructorResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class CoachRatingsRelationManager extends RelationManager
{
    protected static string $relationship = 'ratings';

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['instructor_id'] = $this->ownerRecord->id;
        $data['user_id'] = Auth::id() ?? 1; // Fallback to user ID 1 if no auth

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['instructor_id'] = $this->ownerRecord->id;

        return $data;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('score')
                    ->label('Puntuación')
                    ->required()
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(5)
                    ->helperText('Calificación del 1 al 5'),

                Forms\Components\Textarea::make('review')
                    ->label('Comentario')
                    ->maxLength(500)
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('score')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                // Tables\Columns\TextColumn::make('instructor.name')
                //     ->label('Instructor')
                //     ->sortable()
                //     ->searchable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Usuario')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('score')
                    ->label('Puntuación')
                    ->badge()
                    ->color(fn(string $state): string => match (true) {
                        $state >= 4 => 'success',
                        $state >= 3 => 'warning',
                        default => 'danger',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('review')
                    ->label('Comentario')
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 50) {
                            return null;
                        }
                        return $state;
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                // Tables\Columns\TextColumn::make('updated_at')
                //     ->label('Actualizado')
                //     ->dateTime('d/m/Y H:i')
                //     ->sortable()
                //     ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('score')
                    ->label('Puntuación')
                    ->options([
                        '5' => '⭐⭐⭐⭐⭐ (5)',
                        '4' => '⭐⭐⭐⭐ (4)',
                        '3' => '⭐⭐⭐ (3)',
                        '2' => '⭐⭐ (2)',
                        '1' => '⭐ (1)',
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
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Nueva Calificación'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
