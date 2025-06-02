<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
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
    protected static ?string $navigationGroup = 'Seguridad';

    protected static ?string $navigationLabel = 'Usuarios';

    protected static ?string $label = 'Usuario'; // Nombre en singular
    protected static ?string $pluralLabel = 'Usuarios'; // Nombre en plural

    protected static ?int $navigationSort = 1;


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255),

                Forms\Components\Select::make('roles')
                    ->label('Roles')
                    ->multiple()
                    ->relationship('roles', 'name')
                    ->options(function () {
                        $user = auth()->user();

                        // Si el usuario logueado es super_admin, mostrar todos los roles
                        if ($user && $user->hasRole('super_admin')) {
                            return \Spatie\Permission\Models\Role::pluck('name', 'id');
                        }

                        // Si no es super_admin, excluir ese rol
                        return \Spatie\Permission\Models\Role::where('name', '!=', 'super_admin')->pluck('name', 'id');
                    })
                    ->preload()
                    ->searchable(),

                // Forms\Components\DateTimePicker::make('email_verified_at'),
                // ✅ SOLUCIÓN 1: Campo password con dehydrated condicional
                Forms\Components\TextInput::make('password')
                    ->password()
                    ->label('Contraseña')
                    ->helperText('Dejar vacío para mantener la contraseña actual')
                    ->required(fn($livewire) => $livewire instanceof \Filament\Resources\Pages\CreateRecord)
                    ->dehydrated(fn($state) => filled($state))
                    ->dehydrateStateUsing(fn($state) => filled($state) ? bcrypt($state) : null)
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),

                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Roles'),
                // Tables\Columns\TextColumn::make('email_verified_at')
                //     ->dateTime()
                //     ->sortable(),
                // Tables\Columns\TextColumn::make('created_at')
                //     ->dateTime()
                //     ->sortable()
                //     ->toggleable(isToggledHiddenByDefault: true),
                // Tables\Columns\TextColumn::make('updated_at')
                //     ->dateTime()
                //     ->sortable()
                //     ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('id', 'desc')
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

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (!auth()->user()?->hasRole('super_admin')) {
            // Ocultar usuarios que tengan el rol super_admin
            $query->whereDoesntHave('roles', function ($q) {
                $q->where('name', 'super_admin');
            });
        }

        return $query;
    }



    public static function getRelations(): array
    {
        return [
            RelationManagers\UserPackagesRelationManager::class,
            RelationManagers\UserPaymentMethodRelationManager::class,
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
