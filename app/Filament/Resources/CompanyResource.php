<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CompanyResource\Pages;
use App\Filament\Resources\CompanyResource\RelationManagers;
use App\Models\Company;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Eloquent\Model;

class CompanyResource extends Resource
{
    protected static ?string $model = Company::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationGroup = 'Roles y Seguridad';

    protected static ?string $navigationLabel = 'Administración de la Empresa';

    protected static ?string $label = 'Administración de la Empresa';
    protected static ?string $pluralLabel = 'Administración de la Empresa';

    protected static ?int $navigationSort = 29;

   public static function canCreate(): bool
    {
        return Company::count() === 0;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información General')
                    ->description('Datos básicos de la empresa')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre de la Empresa')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Ej: RSISTANCE'),
                        Forms\Components\TextInput::make('social_reason')
                            ->label('Razón Social')
                            ->maxLength(255)
                            ->placeholder('Ej: RSISTANCE S.A.C.'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Información de Contacto')
                    ->description('Datos de contacto de la empresa')
                    ->schema([
                        Forms\Components\TextInput::make('address')
                            ->label('Dirección')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Ej: Av. Ejemplo 123, Lima, Perú'),
                        Forms\Components\TextInput::make('phone')
                            ->label('Teléfono')
                            ->tel()
                            ->required()
                            ->maxLength(20)
                            ->placeholder('Ej: +51 987654321'),
                        Forms\Components\TextInput::make('phone_whassap')
                            ->label('Teléfono WhatsApp')
                            ->tel()
                            ->required()
                            ->maxLength(20)
                            ->placeholder('Ej: +51 987654321'),
                        Forms\Components\TextInput::make('phone_help')
                            ->label('Teléfono de Ayuda')
                            ->tel()
                            ->required()
                            ->maxLength(20)
                            ->placeholder('Ej: +51 987654321'),
                        Forms\Components\TextInput::make('email')
                            ->label('Correo Electrónico')
                            ->email()
                            ->maxLength(255)
                            ->placeholder('Ej: info@rsistance.com'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Logo y Firma')
                    ->description('Logo y firma de la empresa')
                    ->schema([
                        Forms\Components\FileUpload::make('logo_path')
                            ->label('Logo')
                            ->image()
                            ->directory('company-logos')
                            ->visibility('public')
                            ->helperText('Formatos: PNG, JPG, SVG. Tamaño máximo: 2MB'),
                        Forms\Components\FileUpload::make('signature_image')
                            ->label('Imagen de la Firma')
                            ->image()
                            ->directory('company-logos')
                            ->visibility('public')
                            ->helperText('Formatos: PNG, JPG, SVG. Tamaño máximo: 2MB'),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Forms\Components\Section::make('Redes Sociales')
                    ->description('Configuración de redes sociales de la empresa')
                    ->schema([
                        Forms\Components\Repeater::make('social_networks')
                            ->label('Redes Sociales')
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Nombre de la Red Social')
                                    ->required()
                                    ->placeholder('Ej: Facebook, Instagram, Twitter')
                                    ->maxLength(50),
                                Forms\Components\TextInput::make('url')
                                    ->label('Enlace/URL')
                                    ->required()
                                    ->url()
                                    ->placeholder('Ej: https://www.facebook.com/rsistance')
                                    ->maxLength(255),
                            ])
                            ->columns(2)
                            ->itemLabel(fn (array $state): ?string => $state['name'] ?? 'Nueva Red Social')
                            ->addActionLabel('Agregar Red Social')
                            ->reorderable(true)
                            ->collapsible()
                            ->cloneable()
                            ->defaultItems(0)
                            ->helperText('Agrega las redes sociales de tu empresa con sus respectivos enlaces'),
                    ])
                    ->collapsible(),

                Forms\Components\Section::make('Configuración de Facturación (Greenter)')
                    ->description('Configuración para facturación electrónica')
                    ->schema([
                        Forms\Components\Toggle::make('is_production')
                            ->label('Modo Producción')
                            ->helperText('Activar para facturar en modo producción')
                            ->default(false),
                        Forms\Components\TextInput::make('sol_user_production')
                            ->label('Usuario SOL Producción')
                            ->maxLength(255)
                            ->placeholder('Usuario SOL para producción'),
                        Forms\Components\TextInput::make('sol_user_password_production')
                            ->label('Contraseña SOL Producción')
                            ->password()
                            ->maxLength(255)
                            ->placeholder('Contraseña SOL para producción'),
                        Forms\Components\TextInput::make('cert_path_production')
                            ->label('Ruta del Certificado Producción')
                            ->maxLength(255)
                            ->placeholder('Ruta del certificado digital para producción'),
                        Forms\Components\TextInput::make('client_id_production')
                            ->label('Client ID Producción')
                            ->maxLength(255)
                            ->placeholder('Client ID para producción'),
                        Forms\Components\TextInput::make('client_secret_production')
                            ->label('Client Secret Producción')
                            ->password()
                            ->maxLength(255)
                            ->placeholder('Client secret para producción'),
                        Forms\Components\TextInput::make('sol_user_evidence')
                            ->label('Usuario SOL Pruebas (QA)')
                            ->maxLength(255)
                            ->placeholder('Usuario SOL para pruebas'),
                        Forms\Components\TextInput::make('sol_user_password_evidence')
                            ->label('Contraseña SOL Pruebas (QA)')
                            ->password()
                            ->maxLength(255)
                            ->placeholder('Contraseña SOL para pruebas'),
                        Forms\Components\TextInput::make('cert_path_evidence')
                            ->label('Ruta del Certificado Pruebas (QA)')
                            ->maxLength(255)
                            ->placeholder('Ruta del certificado digital para pruebas'),
                        Forms\Components\TextInput::make('client_id_evidence')
                            ->label('Client ID Pruebas (QA)')
                            ->maxLength(255)
                            ->placeholder('Client ID para pruebas'),
                        Forms\Components\TextInput::make('client_secret_evidence')
                            ->label('Client Secret Pruebas (QA)')
                            ->password()
                            ->maxLength(255)
                            ->placeholder('Client secret para pruebas'),
                    ])
                    ->columns(2)
                    ->collapsible(),
            ]);
    }


  public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('social_reason')
                    ->label('Razón Social')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Teléfono')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('social_networks')
                    ->label('Redes Sociales')
                    ->formatStateUsing(function ($state) {
                        // Validar que sea array y no esté vacío
                        if (!is_array($state) || empty($state)) {
                            return 'Sin redes sociales';
                        }

                        $count = count($state);
                        $names = [];

                        // Validar que cada elemento sea un array con el campo 'name'
                        foreach ($state as $network) {
                            if (is_array($network) && isset($network['name'])) {
                                $names[] = $network['name'];
                            }
                        }

                        if (empty($names)) {
                            return 'Sin redes sociales';
                        }

                        return $count . ' red(es): ' . implode(', ', $names);
                    })
                    ->searchable(false)
                    ->sortable(false),
                Tables\Columns\IconColumn::make('is_production')
                    ->label('Modo Producción')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Última Actualización')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Editar Configuración'),
            ])
            ->bulkActions([
                //
            ])
            ->paginated(false);
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
            'index' => Pages\ListCompanies::route('/'),
            'create' => Pages\CreateCompany::route('/create'),
            'edit' => Pages\EditCompany::route('/{record}/edit'),
        ];
    }
}
