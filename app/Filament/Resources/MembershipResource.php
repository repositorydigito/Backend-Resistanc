<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MembershipResource\Pages;
use App\Filament\Resources\MembershipResource\RelationManagers;
use App\Models\Discipline;
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

                Section::make('Información de la membresía')
                    ->columns(2)
                    ->schema([

                        Forms\Components\FileUpload::make('icon')
                            ->label('Ícono/Logo')
                            ->hint('Suba la imagen del ícono/logo (max. 2MB)')
                            ->image()
                            ->directory('memberships/icons')
                            ->preserveFilenames()
                            ->maxSize(2048) // 2MB
                            ->imageResizeMode('cover')
                            ->imageCropAspectRatio('1:1')
                            ->imageResizeTargetWidth('200')
                            ->imageResizeTargetHeight('200')
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),

                        Forms\Components\TextInput::make('level')
                            ->label('Nivel')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->unique(ignoreRecord: true),

                        Forms\Components\TextInput::make('duration')
                            ->label('Duración (meses)')
                            ->numeric()
                            ->minValue(1)
                            ->default(3)
                            ->required(),

                        Forms\Components\TextInput::make('classes_before')
                            ->label('Días de reserva anticipada')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->required(),

                        Forms\Components\Textarea::make('description')
                            ->label('Descripción')
                            ->columnSpanFull(),

                        Forms\Components\ColorPicker::make('color_hex')
                            ->label('Color representativo')
                            ->required()
                    ]),



                Section::make('Beneficios')
                    ->schema([
                        // Shake
                        Forms\Components\Toggle::make('is_benefit_shake')
                            ->label('¿Beneficio de shake?')
                            ->live()
                            ->default(false),
                        // Forms\Components\Select::make('typeDrink_id')
                        //     ->label('Tipo de bebida')
                        //     ->live()
                        //     ->relationship('typeDrink', 'name')
                        //     ->visible(fn(Forms\Get $get): bool => $get('is_benefit_shake'))
                        //     ->nullable()
                        //     ->required(fn(Forms\Get $get): bool => $get('is_benefit_shake')),

                        Forms\Components\TextInput::make('shake_quantity')
                            ->label('Cantidad de shakes')
                            ->live()
                            ->visible(fn(Forms\Get $get): bool => $get('is_benefit_shake'))
                            ->numeric()
                            ->minValue(1)
                            ->default(1)
                            ->required(fn(Forms\Get $get): bool => $get('is_benefit_shake')),
                        // Fin shake

                        // Disciplinas
                        Forms\Components\Toggle::make('is_benefit_discipline')
                            ->label('¿Beneficio de disciplina?')
                            ->live()
                            ->default(false),
                        Forms\Components\Select::make('discipline_id')
                            ->label('Disciplina')
                            ->relationship('discipline', 'name')
                            ->live()
                            ->visible(fn(Forms\Get $get): bool => $get('is_benefit_discipline'))
                            ->nullable()
                            ->required(fn(Forms\Get $get): bool => $get('is_benefit_discipline')),
                        Forms\Components\TextInput::make('discipline_quantity')
                            ->label('Cantidad de disciplinas')
                            ->live()
                            ->visible(fn(Forms\Get $get): bool => $get('is_benefit_discipline'))
                            ->numeric()
                            ->minValue(1)
                            ->default(1)
                            ->required(fn(Forms\Get $get): bool => $get('is_benefit_discipline')),
                        // Fin disciplinas
                    ]),

                Section::make('Personalización')
                    ->schema([



                        Forms\Components\Repeater::make('colors')
                            ->label('Seleccionar colores')
                            ->schema([
                                Forms\Components\ColorPicker::make('color')
                                    ->label(false)
                                    ->required()
                            ])
                            ->default(null)
                            ->addActionLabel('+ Añadir color')
                            ->collapsible()
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
                Tables\Columns\TextColumn::make('level')
                    ->label('Nivel')
                    ->badge()
                    ->color('info')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('duration')
                    ->label('Duración (meses)')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('classes_before')
                    ->label('Días de reserva anticipada')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('¿Está activa?')
                    ->boolean(),

                Tables\Columns\IconColumn::make('is_benefit_shake')
                    ->label('Beneficio Shake')
                    ->boolean(),

                Tables\Columns\IconColumn::make('is_benefit_discipline')
                    ->label('Beneficio Disciplina')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('¿Está activa?'),
                Tables\Filters\TernaryFilter::make('is_benefit_shake')
                    ->label('Beneficio Shake'),
                Tables\Filters\TernaryFilter::make('is_benefit_discipline')
                    ->label('Beneficio Disciplina'),
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
