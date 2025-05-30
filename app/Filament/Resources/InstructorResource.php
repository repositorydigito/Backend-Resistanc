<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InstructorResource\Pages;
use App\Filament\Resources\InstructorResource\RelationManagers;
use App\Models\Instructor;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class InstructorResource extends Resource
{
    protected static ?string $model = Instructor::class;

    protected static ?string $navigationIcon = 'heroicon-o-hand-raised'; // Fuerza

    protected static ?string $navigationGroup = 'Entrenamiento';

    protected static ?string $navigationLabel = 'Instructores';

    protected static ?string $label = 'Instructor'; // Nombre en singular
    protected static ?string $pluralLabel = 'Instructores'; // Nombre en plural

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name'),
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('phone')
                    ->tel()
                    ->maxLength(15),
                Forms\Components\TextInput::make('specialties')
                    ->required(),
                Forms\Components\Textarea::make('bio')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('certifications'),
                Forms\Components\FileUpload::make('profile_image')
                    ->image(),
                Forms\Components\TextInput::make('instagram_handle')
                    ->maxLength(100),
                Forms\Components\Toggle::make('is_head_coach')
                    ->required(),
                Forms\Components\TextInput::make('experience_years')
                    ->numeric(),
                Forms\Components\TextInput::make('rating_average')
                    ->required()
                    ->numeric()
                    ->default(0.00),
                Forms\Components\TextInput::make('total_classes_taught')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\DatePicker::make('hire_date'),
                Forms\Components\TextInput::make('hourly_rate_soles')
                    ->numeric(),
                Forms\Components\TextInput::make('status')
                    ->required(),
                Forms\Components\TextInput::make('availability_schedule'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Tables\Columns\TextColumn::make('user.name')
                //     ->numeric()
                //     ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Correo Electrónico')
                    ->searchable(),
                // Tables\Columns\TextColumn::make('phone')
                //     ->label('Teléfono')
                //     ->searchable(),
                // Tables\Columns\ImageColumn::make('profile_image'),
                // Tables\Columns\TextColumn::make('instagram_handle')
                //     ->label('Instagram')
                //     ->searchable(),
                Tables\Columns\IconColumn::make('is_head_coach')
                    ->label('¿Es Head Coach?')
                    ->boolean(),
                // Tables\Columns\TextColumn::make('experience_years')
                //     ->label('Años de Experiencia')
                //     ->numeric()
                //     ->sortable(),
                // Tables\Columns\TextColumn::make('rating_average')
                //     ->label('Calificación Promedio')
                //     ->numeric()
                //     ->sortable(),
                Tables\Columns\TextColumn::make('total_classes_taught')
                    ->label('Total de Clases Dictadas')
                    ->numeric()
                    ->sortable(),
                // Tables\Columns\TextColumn::make('hire_date')
                //     ->label('Fecha de Contratación')
                //     ->date()
                //     ->sortable(),
                Tables\Columns\TextColumn::make('hourly_rate_soles')
                    ->label('Tarifa por Hora (S/.)')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'active' => 'Activo',
                        'inactive' => 'Inactivo',
                        'on_leave' => 'En Licencia',
                        'terminated' => 'Terminado',
                        default => 'Desconocido',
                    })
                    ->color(fn(string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'danger',
                        'on_leave' => 'warning',
                        'terminated' => 'gray',
                        default => 'secondary',
                    })
                    ->label('Estado'),
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
            'index' => Pages\ListInstructors::route('/'),
            'create' => Pages\CreateInstructor::route('/create'),
            'edit' => Pages\EditInstructor::route('/{record}/edit'),
        ];
    }
}
