<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MembershipResource\Pages;
use App\Filament\Resources\MembershipResource\RelationManagers;
use App\Models\Membership;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MembershipResource extends Resource
{
    protected static ?string $model = Membership::class;

    protected static ?string $navigationIcon = 'heroicon-o-trophy';

    protected static ?string $navigationGroup = 'Administración';

    protected static ?string $navigationLabel = 'Membresias';

    protected static ?string $label = 'Membresia'; // Nombre en singular
    protected static ?string $pluralLabel = 'Membresias'; // Nombre en plural

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Toggle::make('is_active')
                    ->default(true)
                    ->label('¿Está activa?')
                    ->required(),
                Section::make('Información de la membresia')
                    ->columns(2)
                    ->schema([

                        Forms\Components\TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),

                        Forms\Components\Select::make('level')
                            ->options([
                                'resistance' => 'Resistance',
                                'gold' => 'Gold',
                                'black' => 'Black',
                            ])
                            ->label('Nivel')
                            ->required()
                            ->disableOptionWhen(function ($value, $record) {
                                // Verificar si este nivel ya está en uso por otro registro
                                return \App\Models\Membership::where('level', $value)
                                    ->where('id', '!=', $record?->id ?? 0)
                                    ->exists();
                            }),

                        Forms\Components\ColorPicker::make('color_hex')
                            ->label('Color')
                            ->required()
                            ->default('#000000'),

                        Forms\Components\TextInput::make('display_order')
                            ->label('Orden de visualización')
                            ->unique(ignoreRecord: true)
                            ->required()
                            ->numeric()
                            ->default(0),

                        Forms\Components\Textarea::make('description')
                            ->dehydrated(true)
                            ->label('Descripción')
                            ->columnSpanFull(),
                        // En lugar de TextInput, usar:
                        Forms\Components\TagsInput::make('benefits')
                            ->dehydrated(true)
                            ->label('Beneficios')
                            ->required()
                            ->placeholder('Presiona Enter después de cada beneficio')
                            ->columnSpanFull(),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable(),
                // Tables\Columns\TextColumn::make('slug')
                //     ->searchable(),
                Tables\Columns\TextColumn::make('level')
                    ->label('Nivel')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'resistance' => 'Resistance',
                        'gold' => 'Gold',
                        'black' => 'Black',
                        default => 'Desconocido',
                    })
                    ->color(fn(string $state): string => match ($state) {
                        'resistance' => 'info',
                        'gold' => 'warning',
                        'black' => 'black',
                        default => 'gray',
                    })
                    ->searchable(),

                Tables\Columns\ColorColumn::make('color_hex')
                    ->label('Color')
                    ->searchable(),

                // Tables\Columns\TextColumn::make('color_hex')
                //     ->label('Color')
                //     ->searchable(),


                Tables\Columns\TextColumn::make('display_order')
                    ->label('Orden de visualización')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('¿Está activa?')
                    ->boolean(),
                // Tables\Columns\TextColumn::make('created_at')
                //     ->dateTime()
                //     ->sortable()
                //     ->toggleable(isToggledHiddenByDefault: true),
                // Tables\Columns\TextColumn::make('updated_at')
                //     ->dateTime()
                //     ->sortable()
                //     ->toggleable(isToggledHiddenByDefault: true),
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
            'index' => Pages\ListMemberships::route('/'),
            'create' => Pages\CreateMembership::route('/create'),
            'edit' => Pages\EditMembership::route('/{record}/edit'),
        ];
    }
}
