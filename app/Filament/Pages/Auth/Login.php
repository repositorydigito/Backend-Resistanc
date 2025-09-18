<?php

namespace App\Filament\Pages\Auth;

use Filament\Forms\Components\Checkbox;
use Filament\Pages\Auth\Login as BaseLogin;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Components\View;

class Login extends BaseLogin
{
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                // View::make('filament.pages.auth.partials.login-header'),
                TextInput::make('email')
                    ->label('Usuario')
                    ->placeholder('Ingresa tu usuario o correo electrónico')
                    ->required()
                    ->autocomplete(),
                TextInput::make('password')
                    ->label('Contraseña')
                    // ->placeholder('')
                    ->password()
                    ->revealable() // Permite mostrar/ocultar la contraseña
                    ->required(),
                Checkbox::make('remember')
                    ->label('Recordar sesión'), // Campo para "Recordar sesión"
            ])
            ->columns(1)
            ->extraAttributes([
                // 'class' => 'bg-[#232323] dark:bg-gray-900 p-10 rounded-2xl shadow-2xl border border-[#B0694C]'
            ]);
    }

    protected function getFormActions(): array
    {
        return [
            $this->getAuthenticateFormAction(),
        ];
    }

    protected function getAuthenticateFormAction(): \Filament\Actions\Action
    {
        return \Filament\Actions\Action::make('authenticate')
            ->label('Iniciar Sesión') // Cambiar el texto del botón
            ->submit('authenticate');
    }
}
