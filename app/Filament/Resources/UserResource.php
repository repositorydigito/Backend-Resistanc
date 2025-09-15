<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UserResource extends Resource
{
    protected static ?string $model = User::class;



    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'Roles y Seguridad';

    protected static ?string $navigationLabel = 'Usuarios';

    protected static ?string $label = 'Usuario'; // Nombre en singular
    protected static ?string $pluralLabel = 'Usuarios'; // Nombre en plural

    protected static ?int $navigationSort = 28;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Sección 1: Credenciales básicas
                Section::make('Credenciales de acceso')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre de usuario')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('email')
                            ->label('Correo electrónico')
                            ->email()
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('password')
                            ->password()
                            ->label('Contraseña')
                            ->helperText('Dejar vacío para mantener la contraseña actual')
                            ->required(fn($livewire) => $livewire instanceof \Filament\Resources\Pages\CreateRecord)
                            ->dehydrated(fn($state) => filled($state))
                            ->dehydrateStateUsing(fn($state) => filled($state) ? bcrypt($state) : null)
                            ->maxLength(255),

                        Forms\Components\Select::make('roles')
                            ->label('Roles')
                            ->multiple()
                            ->relationship('roles', 'name')
                            ->options(function () {
                                // Obtenemos el usuario autenticado una sola vez
                                $user = auth()->user();

                                // Consulta base excluyendo siempre ciertos roles
                                $query = \Spatie\Permission\Models\Role::query()
                                    ->whereNotIn('name', ['cliente', 'instructor']);

                                // Solo super_admin puede ver y asignar el rol super_admin
                                if ($user && $user->hasRole('super_admin')) {
                                    return $query->orWhere('name', 'super_admin')
                                        ->pluck('name', 'id');
                                }

                                return $query->pluck('name', 'id');
                            })
                            ->preload()
                            ->searchable(),
                    ]),

                // Sección 2: Perfil personal
                // Section::make('Información personal')
                //     ->schema([
                //         Forms\Components\FileUpload::make('profile.profile_image')
                //             ->label('Imagen de perfil')
                //             ->columnSpanFull()
                //             ->image()
                //             ->directory('user/profile')
                //             ->disk('public')
                //             ->visibility('public')
                //             ->extraAttributes(['class' => 'h-64 w-64'])
                //             ->preserveFilenames(),

                //         Section::make('Datos personales')
                //             ->columns(2)
                //             ->schema([
                //                 Forms\Components\TextInput::make('profile.first_name')
                //                     ->label('Nombre')
                //                     ->maxLength(60)
                //                     ->required(),

                //                 Forms\Components\TextInput::make('profile.last_name')
                //                     ->label('Apellido')
                //                     ->maxLength(60)
                //                     ->required(),

                //                 Forms\Components\DatePicker::make('profile.birth_date')
                //                     ->label('Fecha de nacimiento')
                //                     ->required(),

                //                 Forms\Components\Select::make('profile.gender')
                //                     ->label('Género')
                //                     ->options([
                //                         'female' => 'Femenino',
                //                         'male' => 'Masculino',
                //                         'other' => 'Otro',
                //                         'na' => 'Prefiero no decirlo',
                //                     ])
                //                     ->required(),

                //                 Forms\Components\TextInput::make('profile.shoe_size_eu')
                //                     ->label('Talla de calzado (EU)')
                //                     ->numeric(),
                //             ]),

                //         Section::make('Información adicional')
                //             ->columns(2)
                //             ->schema([
                //                 Forms\Components\TextInput::make('profile.emergency_contact_name')
                //                     ->label('Contacto de emergencia')
                //                     ->maxLength(100),

                //                 Forms\Components\TextInput::make('profile.emergency_contact_phone')
                //                     ->label('Teléfono emergencia')
                //                     ->maxLength(15),
                //             ]),

                //         Section::make('Detalles médicos y objetivos')
                //             ->schema([
                //                 Forms\Components\Textarea::make('profile.medical_conditions')
                //                     ->label('Condiciones médicas')
                //                     ->columnSpanFull(),

                //                 Forms\Components\Textarea::make('profile.fitness_goals')
                //                     ->label('Objetivos fitness')
                //                     ->columnSpanFull(),

                //                 Forms\Components\Textarea::make('profile.bio')
                //                     ->label('Biografía')
                //                     ->columnSpanFull(),
                //             ]),
                //     ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Tables\Columns\ImageColumn::make('profile.profile_image')
                //     ->label('')
                //     ->circular()
                //     ->defaultImageUrl(fn($record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->name) . '&color=FFFFFF&background=111827'),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Correo')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Roles')
                    ->badge()
                    ->color('primary')
                    ->formatStateUsing(fn($state) => ucfirst($state)),

                Tables\Columns\TextColumn::make('profile.first_name')
                    ->label('Nombre completo')
                    ->formatStateUsing(fn($state, $record) => $record->profile?->first_name . ' ' . $record->profile?->last_name)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('roles')
                    ->label('Roles')
                    ->multiple()
                    ->preload()
                    ->relationship('roles', 'name')
                    ->options(function () {
                        $user = auth()->user();
                        if ($user && $user->hasRole('super_admin')) {
                            return \Spatie\Permission\Models\Role::pluck('name', 'id');
                        }
                        return \Spatie\Permission\Models\Role::where('name', '!=', 'super_admin')->pluck('name', 'id');
                    }),

                Tables\Filters\Filter::make('verified')
                    ->label('Solo verificados')
                    ->query(fn($query) => $query->whereNotNull('email_verified_at'))
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->icon('heroicon-o-pencil'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->icon('heroicon-o-trash')
                        ->requiresConfirmation(),
                ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        // Todos los usuarios (incluyendo super_admin) no ven los clientes
        $query->whereDoesntHave('roles', function ($q) {
            $q->where('name', 'cliente')
                ->orWhere('name', 'instructor');
        });

        // Solo los no super_admin no ven otros super_admin
        if (!auth()->user()?->hasRole('super_admin')) {
            $query->whereDoesntHave('roles', function ($q) {
                $q->where('name', 'super_admin');
            });
        }

        return $query;
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
