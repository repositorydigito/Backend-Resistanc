<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserProfileResource\Pages;
use App\Filament\Resources\UserProfileResource\RelationManagers;
use App\Mail\EmailVerification;
use App\Mail\EmailVerificationMailable;
use App\Models\User;
use App\Models\UserProfile;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Mockery\Matcher\Not;

class UserProfileResource extends Resource
{
    protected static ?string $model = UserProfile::class;


    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'Configuración General';

    protected static ?string $navigationLabel = 'Clientes'; // Etiqueta en el menú de navegación

    protected static ?string $label = 'Cliente'; // Nombre en singular
    protected static ?string $pluralLabel = 'Clientes'; // Nombre en plural

    protected static ?int $navigationSort = 19;



    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Toggle::make('is_active')
                    ->label('Activo')
                    ->default(true)
                    ->helperText('Indica si el perfil está activo')
                    ->columnSpanFull()
                    ->visibleOn('edit'),
                // Sección 1: Información de usuario
                Forms\Components\Section::make('Información de Usuario')
                    ->icon('heroicon-o-user')
                    ->description('Datos de acceso al sistema')
                    ->schema([
                        Forms\Components\TextInput::make('user_email')
                            ->label('Correo electrónico')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->placeholder('ejemplo@correo.com')
                            ->helperText('Este será el correo de acceso al sistema')
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('user_password')
                            ->label('Contraseña')
                            ->password()
                            ->minLength(8)
                            ->confirmed()
                            ->helperText('Mínimo 8 caracteres')
                            ->required()
                            ->columnSpanFull()
                            ->visibleOn('create'),

                        Forms\Components\TextInput::make('user_password_confirmation')
                            ->label('Confirmar contraseña')
                            ->password()
                            ->required()
                            ->columnSpanFull()
                            ->visibleOn('create'),

                        Forms\Components\TextInput::make('user_password_edit')
                            ->label('Contraseña')
                            ->password()
                            ->minLength(8)
                            ->confirmed()
                            ->helperText('Deja vacío para mantener la contraseña actual')
                            ->columnSpanFull()
                            ->visibleOn('edit'),

                        Forms\Components\TextInput::make('user_password_confirmation_edit')
                            ->label('Confirmar contraseña')
                            ->password()
                            ->columnSpanFull()
                            ->visibleOn('edit'),
                    ]),

                // Sección 2: Información básica del perfil
                Forms\Components\Section::make('Información Personal')
                    ->icon('heroicon-o-identification')
                    ->columns(2)
                    ->schema([
                        Forms\Components\FileUpload::make('profile_image')
                            ->label('Foto de perfil')
                            ->image()
                            ->directory('user-profiles')
                            ->columnSpanFull()
                            ->alignCenter(),

                        Forms\Components\TextInput::make('first_name')
                            ->label('Nombre(s)')
                            ->required()
                            ->maxLength(60),

                        Forms\Components\TextInput::make('last_name')
                            ->label('Apellidos')
                            ->required()
                            ->maxLength(60),

                        Forms\Components\DatePicker::make('birth_date')
                            ->label('Fecha de nacimiento')
                            ->native(false)
                            ->displayFormat('d/m/Y'),

                        Forms\Components\Select::make('gender')
                            ->label('Género')
                            ->options([
                                'male' => 'Masculino',
                                'female' => 'Femenino',
                                'other' => 'Otro',
                                'prefer_not_to_say' => 'Prefiero no decir',
                            ])
                            ->required(),

                        Forms\Components\TextInput::make('shoe_size_eu')
                            ->label('Talla de calzado (EU)')
                            ->numeric()
                            ->minValue(20)
                            ->maxValue(50),
                    ]),

                // Sección 3: Información de contacto y emergencia
                Forms\Components\Section::make('Contacto de Emergencias')
                    ->icon('heroicon-o-phone')
                    ->columns(2)
                    ->schema([
                        // Forms\Components\Select::make('user_id')
                        //     ->label('Usuario asociado')
                        //     ->relationship('user', 'name')
                        //     ->required()
                        //     ->columnSpanFull()
                        //     ->visibleOn('edit'),

                        Forms\Components\TextInput::make('emergency_contact_name')
                            ->label('Nombre')
                            ->placeholder('Nombre de la persona a contactar')
                            ->maxLength(100),

                        Forms\Components\TextInput::make('emergency_contact_phone')
                            ->label('Teléfono')
                            ->tel()
                            ->maxLength(15),
                    ]),

                // Sección 4: Información adicional
                Forms\Components\Section::make('Información Adicional')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        // Forms\Components\Textarea::make('bio')
                        //     ->label('Biografía')
                        //     ->columnSpanFull()
                        //     ->maxLength(500),

                        Forms\Components\Textarea::make('medical_conditions')
                            ->label('Condiciones médicas')
                            ->columnSpanFull()
                            ->maxLength(500),

                        // Forms\Components\Textarea::make('fitness_goals')
                        //     ->label('Objetivos de fitness')
                        //     ->columnSpanFull()
                        //     ->maxLength(500),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Tables\Columns\ImageColumn::make('profile_image')
                //     ->label('')
                //     ->circular()
                //     ->defaultImageUrl(url('/images/default-avatar.png')),

                Tables\Columns\TextColumn::make('full_name')
                    ->label('Nombre')
                    ->formatStateUsing(fn(UserProfile $record) => "{$record->first_name} {$record->last_name}")
                    ->searchable(['first_name', 'last_name']),

                Tables\Columns\TextColumn::make('birth_date')
                    ->label('Edad')
                    ->formatStateUsing(fn($state) => $state?->age . ' años')
                    ->sortable(),

                Tables\Columns\TextColumn::make('gender')
                    ->label('Género')
                    ->formatStateUsing(fn($state) => match ($state) {
                        'male' => 'Masculino',
                        'female' => 'Femenino',
                        default => 'Otro'
                    }),

                Tables\Columns\TextColumn::make('user.email')
                    ->label('Correo electrónico')
                    ->sortable(),

                Tables\Columns\IconColumn::make('user.email_verified_at')
                    ->label('Email Verificado')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->getStateUsing(fn($record) => $record->user?->hasVerifiedEmail())
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('gender')
                    ->label('Género')
                    ->options([
                        'male' => 'Masculino',
                        'female' => 'Femenino',
                        'other' => 'Otro',
                    ]),

                Tables\Filters\TernaryFilter::make('email_verified')
                    ->label('Email Verificado')
                    ->placeholder('Todos los clientes')
                    ->trueLabel('Email verificado')
                    ->falseLabel('Email no verificado')
                    ->queries(
                        true: fn(Builder $query) => $query->whereHas('user', fn($q) => $q->whereNotNull('email_verified_at')),
                        false: fn(Builder $query) => $query->whereHas('user', fn($q) => $q->whereNull('email_verified_at')),
                        blank: fn(Builder $query) => $query,
                    ),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),

                Tables\Actions\Action::make('resend_verification')
                    ->label('Reenviar verificación')
                    ->icon('heroicon-o-envelope')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Reenviar email de verificación')
                    ->modalDescription('¿Estás seguro de que quieres reenviar el email de verificación a este cliente?')
                    ->modalSubmitActionLabel('Sí, reenviar')
                    ->action(function (UserProfile $record) {
                        try {
                            Log::info('=== INICIANDO ACCIÓN DE REENVÍO ===');
                            Log::info('UserProfile ID: ' . $record->id);

                            if (!$record->user) {
                                Log::error('No se encontró el usuario asociado');
                                Notification::make()
                                    ->title('Error')
                                    ->danger()
                                    ->body('No se encontró el usuario asociado.')
                                    ->send();
                                return;
                            }

                            $email = $record->user->email;
                            Log::info('Email del usuario: ' . $email);

                            if (empty($email)) {
                                Log::error('El correo electrónico del usuario está vacío');
                                Notification::make()
                                    ->title('Error')
                                    ->danger()
                                    ->body('El correo electrónico del usuario está vacío.')
                                    ->send();
                                return;
                            }

                            Log::info('Enviando correo a: ' . $email);

                            // Enviar correo de verificación
                            Mail::to($email)->send(new EmailVerificationMailable($record->user));

                            Log::info('Correo enviado exitosamente');

                            Notification::make()
                                ->title('Email enviado')
                                ->success()
                                ->body("Correo enviado a: {$email}")
                                ->send();

                        } catch (\Exception $e) {
                            Log::error('Error: ' . $e->getMessage());
                            Notification::make()
                                ->title('Error')
                                ->danger()
                                ->body('Error: ' . $e->getMessage())
                                ->send();
                        }
                    })
                    ->visible(fn(UserProfile $record) => $record->user && !$record->user->hasVerifiedEmail()),
                // ->authorize('resendVerification'),

                // Tables\Actions\DeleteAction::make()
                //     ->icon('heroicon-o-trash')
                //     ->color('danger')
                //     ->requiresConfirmation()
                //     ->modalHeading('Eliminar cliente')
                //     ->modalDescription('¿Estás seguro de que quieres eliminar este cliente? Esta acción también eliminará el usuario asociado y no se puede deshacer.')
                //     ->modalSubmitActionLabel('Sí, eliminar')
                //     ->action(function (UserProfile $record) {
                //         // Eliminar el usuario relacionado primero
                //         if ($record->user) {
                //             $record->user->delete();
                //         }
                //         // Luego eliminar el perfil
                //         $record->delete();
                //     }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Tables\Actions\DeleteBulkAction::make()
                    //     ->icon('heroicon-o-trash')
                    //     ->action(function ($records) {
                    //         foreach ($records as $record) {
                    //             // Eliminar el usuario relacionado primero
                    //             if ($record->user) {
                    //                 $record->user->delete();
                    //             }
                    //             // Luego eliminar el perfil
                    //             $record->delete();
                    //         }
                    //     }),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\UserPackagesRelationManager::class,
            RelationManagers\UserMembershipsRelationManager::class,
            RelationManagers\UserPaymentMethodRelationManager::class,
            RelationManagers\ClassScheduleSeatsRelationManager::class,
            RelationManagers\DrinkUserRelationManager::class,
            RelationManagers\UserFavorityRelationManager::class,
            RelationManagers\UserWaitingClassRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUserProfiles::route('/'),
            'create' => Pages\CreateUserProfile::route('/create'),
            'edit' => Pages\EditUserProfile::route('/{record}/edit'),
        ];
    }
}
