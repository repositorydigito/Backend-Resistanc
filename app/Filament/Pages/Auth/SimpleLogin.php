<?php

namespace App\Filament\Pages\Auth;

use Filament\Pages\Auth\Login as BaseLogin;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;

class SimpleLogin extends BaseLogin
{
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('email')
                    ->label('Usuario') // Solo cambiar el label
                    ->placeholder('Ingresa tu usuario') // Placeholder personalizado
                    ->required()
                    ->autocomplete(),
                TextInput::make('password')
                    ->label('Contraseña')
                    ->password()
                    ->required(),
            ]);
    }

       protected function getAuthenticateFormAction(): \Filament\Actions\Action
    {
        return \Filament\Actions\Action::make('authenticate')
            ->label('Iniciar Sesión') // Cambiar el texto del botón
            ->submit('authenticate');
    }
}
