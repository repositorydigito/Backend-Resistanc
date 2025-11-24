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
                        Forms\Components\TextInput::make('ruc')
                            ->label('RUC')
                            ->maxLength(11)
                            ->placeholder('Ej: 20123456789')
                            ->numeric(),
                        Forms\Components\TextInput::make('commercial_name')
                            ->label('Nombre Comercial')
                            ->maxLength(255)
                            ->placeholder('Ej: RSISTANCE'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Información de Contacto')
                    ->description('Datos de contacto de la empresa')
                    ->schema([
                        Forms\Components\TextInput::make('address')
                            ->label('Dirección Completa')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Ej: Av. Ejemplo 123'),
                        Forms\Components\TextInput::make('ubigeo')
                            ->label('Ubigeo')
                            ->maxLength(6)
                            ->placeholder('Ej: 150101')
                            ->helperText('Código de ubicación geográfica de 6 dígitos'),
                        Forms\Components\TextInput::make('department')
                            ->label('Departamento')
                            ->maxLength(100)
                            ->placeholder('Ej: LIMA'),
                        Forms\Components\TextInput::make('province')
                            ->label('Provincia')
                            ->maxLength(100)
                            ->placeholder('Ej: LIMA'),
                        Forms\Components\TextInput::make('district')
                            ->label('Distrito')
                            ->maxLength(100)
                            ->placeholder('Ej: LIMA'),
                        Forms\Components\TextInput::make('urbanization')
                            ->label('Urbanización')
                            ->maxLength(100)
                            ->default('-')
                            ->placeholder('Ej: -'),
                        Forms\Components\TextInput::make('establishment_code')
                            ->label('Código de Establecimiento')
                            ->maxLength(4)
                            ->default('0000')
                            ->placeholder('Ej: 0000')
                            ->helperText('Código de establecimiento asignado por SUNAT'),
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


                Forms\Components\Section::make('Redes Sociales - URLs Individuales')
                    ->description('Enlaces a las redes sociales de la empresa')
                    ->schema([
                        Forms\Components\TextInput::make('facebook_url')
                            ->label('Facebook URL')
                            ->url()
                            ->maxLength(255)
                            ->placeholder('Ej: https://facebook.com/empresa'),
                        Forms\Components\TextInput::make('instagram_url')
                            ->label('Instagram URL')
                            ->url()
                            ->maxLength(255)
                            ->placeholder('Ej: https://instagram.com/empresa'),
                        Forms\Components\TextInput::make('twitter_url')
                            ->label('Twitter URL')
                            ->url()
                            ->maxLength(255)
                            ->placeholder('Ej: https://twitter.com/empresa'),
                        Forms\Components\TextInput::make('linkedin_url')
                            ->label('LinkedIn URL')
                            ->url()
                            ->maxLength(255)
                            ->placeholder('Ej: https://linkedin.com/company/empresa'),
                        Forms\Components\TextInput::make('youtube_url')
                            ->label('YouTube URL')
                            ->url()
                            ->maxLength(255)
                            ->placeholder('Ej: https://youtube.com/empresa'),
                        Forms\Components\TextInput::make('tiktok_url')
                            ->label('TikTok URL')
                            ->url()
                            ->maxLength(255)
                            ->placeholder('Ej: https://tiktok.com/@empresa'),
                        Forms\Components\TextInput::make('whatsapp_url')
                            ->label('WhatsApp URL')
                            ->url()
                            ->maxLength(255)
                            ->placeholder('Ej: https://wa.me/51987654321'),
                        Forms\Components\TextInput::make('website_url')
                            ->label('Sitio Web URL')
                            ->url()
                            ->maxLength(255)
                            ->placeholder('Ej: https://empresa.com'),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Forms\Components\Section::make('Configuración de Facturación')
                    ->description('Configuración general de facturación electrónica')
                    ->schema([
                        Forms\Components\TextInput::make('invoice_series')
                            ->label('Serie de Facturación')
                            ->maxLength(4)
                            ->default('F001')
                            ->placeholder('Ej: F001')
                            ->helperText('Serie para las facturas (ej: F001, B001)'),
                        Forms\Components\TextInput::make('invoice_initial_correlative')
                            ->label('Correlativo Inicial')
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->helperText('Número inicial para el correlativo de facturación'),
                        Forms\Components\TextInput::make('stripe_commission_percentage')
                            ->label('Comisión de Stripe (%)')
                            ->numeric()
                            ->default(3.60)
                            ->suffix('%')
                            ->minValue(0)
                            ->maxValue(100)
                            ->step(0.01)
                            ->helperText('Porcentaje de comisión que Stripe consume de cada venta (ej: 3.60 = 3.60%)'),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Forms\Components\Section::make('Configuración de Producción (Greenter)')
                    ->description('Credenciales para facturación electrónica en modo producción')
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
                        Forms\Components\FileUpload::make('cert_path_production')
                            ->label('Certificado Digital Producción')
                            ->directory('company-certificates')
                            ->visibility('private')
                            ->acceptedFileTypes(['text/plain', 'application/x-pem-file'])
                            ->helperText('Formatos permitidos: .pem, .txt. Archivo del certificado digital para producción'),
                        Forms\Components\TextInput::make('client_id_production')
                            ->label('Client ID Producción')
                            ->maxLength(255)
                            ->placeholder('Client ID para producción'),
                        Forms\Components\TextInput::make('client_secret_production')
                            ->label('Client Secret Producción')
                            ->password()
                            ->maxLength(255)
                            ->placeholder('Client secret para producción'),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Forms\Components\Section::make('Configuración de Pruebas - QA (Greenter)')
                    ->description('Credenciales para facturación electrónica en modo de pruebas')
                    ->schema([
                        Forms\Components\TextInput::make('sol_user_evidence')
                            ->label('Usuario SOL Pruebas (QA)')
                            ->maxLength(255)
                            ->placeholder('Usuario SOL para pruebas'),
                        Forms\Components\TextInput::make('sol_user_password_evidence')
                            ->label('Contraseña SOL Pruebas (QA)')
                            ->password()
                            ->maxLength(255)
                            ->placeholder('Contraseña SOL para pruebas'),
                        Forms\Components\FileUpload::make('cert_path_evidence')
                            ->label('Certificado Digital Pruebas (QA)')
                            ->directory('company-certificates')
                            ->visibility('private')
                            // ->acceptedFileTypes(['text/plain', 'application/x-pem-file'])
                            ->helperText('Formatos permitidos: .pem, .txt. Archivo del certificado digital para pruebas'),
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
