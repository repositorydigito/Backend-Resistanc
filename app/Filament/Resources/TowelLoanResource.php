<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TowelLoanResource\Pages;
use App\Filament\Resources\TowelLoanResource\RelationManagers;
use App\Models\TowelLoan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TowelLoanResource extends Resource
{
    protected static ?string $model = TowelLoan::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationGroup = 'Toallas';

    protected static ?string $navigationLabel = 'Historial de préstamos';

    protected static ?string $label = 'Préstamo de toalla';

    protected static ?string $pluralLabel = 'Préstamos de toalla';

    protected static ?int $navigationSort = 7;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('towel_id')
                    ->label('Toalla')
                    ->relationship('towel', 'code')
                    ->searchable()
                    ->required()
                    ->disabled(fn($isEdit) => $isEdit),

                Forms\Components\Select::make('user_client_id')
                    ->label('Cliente')
                    ->relationship('userClient', 'name')
                    ->searchable()
                    ->required()
                    ->disabled(fn($isEdit) => $isEdit),

                Forms\Components\DateTimePicker::make('loan_date')
                    ->label('Fecha de préstamo')
                    ->required()
                    ->disabled(fn($isEdit) => $isEdit),

                Forms\Components\DateTimePicker::make('estimated_return_date')
                    ->label('Fecha estimada de devolución')
                    ->disabled(fn($isEdit) => $isEdit),

                Forms\Components\DateTimePicker::make('return_date')
                    ->label('Fecha de devolución')
                    ->disabled(fn($isEdit) => $isEdit),

                Forms\Components\Select::make('status')
                    ->label('Estado')
                    ->options([
                        'in_use' => 'En uso',
                        'returned' => 'Devuelto',
                        'overdue' => 'Vencido',
                        'lost' => 'Perdido',
                    ])
                    ->required()
                    ->disabled(fn($isEdit) => $isEdit),

                Forms\Components\Textarea::make('notes')
                    ->label('Notas')
                    ->rows(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('towel.code')
                    ->label('Código de toalla')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('towel.size')
                    ->label('Tamaño')
                    ->sortable(),

                Tables\Columns\TextColumn::make('towel.color')
                    ->label('Color')
                    ->sortable(),

                Tables\Columns\TextColumn::make('userClient.name')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('loan_date')
                    ->label('Fecha de préstamo')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('return_date')
                    ->label('Fecha de devolución')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'in_use' => 'primary',
                        'returned' => 'success',
                        'overdue' => 'warning',
                        'lost' => 'danger'
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'in_use' => 'En uso',
                        'returned' => 'Devuelto',
                        'overdue' => 'Vencido',
                        'lost' => 'Perdido'
                    }),

                // Tables\Columns\TextColumn::make('notes')
                //     ->label('Notas')
                //     ->limit(50),
            ])
            ->defaultSort('loan_date', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'in_use' => 'En uso',
                        'returned' => 'Devuelto',
                        'overdue' => 'Vencido',
                        'lost' => 'Perdido',
                    ])
                    ->multiple(),
            ])
            ->actions([
                // Tables\Actions\ViewAction::make(),
                // Tables\Actions\EditAction::make(),
                // Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListTowelLoans::route('/'),
            'create' => Pages\CreateTowelLoan::route('/create'),
            'edit' => Pages\EditTowelLoan::route('/{record}/edit'),
        ];
    }
}
