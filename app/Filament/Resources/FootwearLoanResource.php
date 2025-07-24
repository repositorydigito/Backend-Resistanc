<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FootwearLoanResource\Pages;
use App\Filament\Resources\FootwearLoanResource\RelationManagers;
use App\Models\FootwearLoan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class FootwearLoanResource extends Resource
{
    protected static ?string $model = FootwearLoan::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';


    protected static ?string $navigationGroup = 'Calzados'; // Nombre del grupo de navegación

    protected static ?string $navigationLabel = 'Historial de préstamos'; // Nombre del grupo de navegación

    protected static ?string $label = 'Historial de préstamo'; // Nombre en singular
    protected static ?string $pluralLabel = 'Historial de préstamos'; // Nombre en plural

    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('footwear_id')
                    ->label('Calzado')
                    ->relationship('footwear', 'code')
                    ->required()
                    ->searchable(),

                Forms\Components\Select::make('user_id')
                    ->label('Socio')
                    ->relationship('user', 'name')
                    ->required()
                    ->searchable(),

                Forms\Components\DateTimePicker::make('loan_date')
                    ->label('Fecha préstamo')
                    ->default(now())
                    ->required(),

                Forms\Components\DateTimePicker::make('estimated_return_date')
                    ->label('Fecha devolución estimada')
                    ->required(),

                Forms\Components\DateTimePicker::make('return_date')
                    ->label('Fecha devolución real'),

                Forms\Components\Select::make('status')
                    ->options([
                        'in_use' => 'Prestado',
                        'returned' => 'Devuelto',
                        'overdue' => 'Vencido',
                        'lost' => 'Perdido'
                    ])
                    ->default('in_use')
                    ->required(),

                Forms\Components\Textarea::make('notes')
                    ->label('Observaciones')
                    ->columnSpanFull()
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('footwear.code')
                    ->label('Código calzado')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('userClient.name')
                    ->label('Cliente')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('loan_date')
                    ->label('Préstamo')
                    ->dateTime(),

                Tables\Columns\TextColumn::make('estimated_return_date')
                    ->label('Devolución estimada')
                    ->dateTime(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'in_use' => 'info',
                        'returned' => 'success',
                        'overdue' => 'danger',
                        'lost' => 'warning',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'in_use' => 'Prestado',
                        'returned' => 'Devuelto',
                        'overdue' => 'Vencido',
                        'lost' => 'Perdido',
                    }),
            ])
            ->defaultSort('loan_date', 'desc');
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
            'index' => Pages\ListFootwearLoans::route('/'),
            'create' => Pages\CreateFootwearLoan::route('/create'),
            'edit' => Pages\EditFootwearLoan::route('/{record}/edit'),
        ];
    }
}
