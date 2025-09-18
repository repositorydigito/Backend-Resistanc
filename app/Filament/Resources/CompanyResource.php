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

    // Solo permitir una empresa
    public static function canCreate(): bool
    {
        return Company::count() === 0;
    }

    public static function canDelete(Model $record): bool
    {
        return false; // No permitir eliminar la configuración
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
                            ->placeholder('Ej: Resistance Gym'),

                        Forms\Components\TextInput::make('legal_name')
                            ->label('Razón Social')
                            ->maxLength(255)
                            ->placeholder('Ej: Resistance Gym S.A.C.'),

                        Forms\Components\TextInput::make('tax_id')
                            ->label('RUC')
                            ->required()
                            ->maxLength(20)
                            ->placeholder('Ej: 20123456789'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Información de Contacto')
                    ->description('Datos de contacto de la empresa')
                    ->schema([
                        Forms\Components\TextInput::make('address')
                            ->label('Dirección')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Ej: Av. Principal 123, Lima'),

                        Forms\Components\TextInput::make('phone')
                            ->label('Teléfono')
                            ->tel()
                            ->required()
                            ->maxLength(20)
                            ->placeholder('Ej: +51 1 234 5678'),

                        Forms\Components\TextInput::make('email')
                            ->label('Correo Electrónico')
                            ->email()
                            ->maxLength(255)
                            ->placeholder('Ej: info@resistancegym.com'),

                        Forms\Components\TextInput::make('website')
                            ->label('Sitio Web')
                            ->url()
                            ->maxLength(255)
                            ->placeholder('Ej: https://www.resistancegym.com'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Configuración del Sistema')
                    ->description('Configuraciones técnicas del sistema')
                    ->schema([
                        Forms\Components\TextInput::make('timezone')
                            ->label('Zona Horaria')
                            ->required()
                            ->maxLength(255)
                            ->default('America/Lima')
                            ->placeholder('Ej: America/Lima'),

                        Forms\Components\TextInput::make('currency')
                            ->label('Moneda')
                            ->required()
                            ->maxLength(3)
                            ->default('PEN')
                            ->placeholder('Ej: PEN'),

                        Forms\Components\TextInput::make('locale')
                            ->label('Idioma')
                            ->required()
                            ->maxLength(5)
                            ->default('es_PE')
                            ->placeholder('Ej: es_PE'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Logo de la Empresa')
                    ->description('Logo que aparecerá en el sistema')
                    ->schema([
                        Forms\Components\FileUpload::make('logo_path')
                            ->label('Logo')
                            ->image()
                            // ->imageEditor()
                            // ->imageCropAspectRatio('16:9')
                            // ->imageResizeTargetWidth('1920')
                            // ->imageResizeTargetHeight('1080')

                            ->directory('company-logos')
                            ->visibility('public')
                            ->helperText('Formatos: PNG, JPG, SVG. Tamaño máximo: 2MB'),
                    ])
                    ->collapsible(),

                Forms\Components\Section::make('Información de Facturación')
                    ->description('Datos de facturación de la empresa')
                    ->schema([
                        Forms\Components\TextInput::make('url_facturacion')
                            ->label('Proveedor de Facturación')
                            ->url()
                            ->maxLength(255)
                            ->placeholder('Ej: https://api.facturacion.com'),

                        Forms\Components\TextInput::make('token_facturacion')
                            ->label('Token de Acceso')
                            ->password()
                            ->maxLength(255)
                            ->placeholder('Ingrese su token de acceso'),

                    ]),

                Forms\Components\Section::make('Configuraciones Adicionales')
                    ->description('Otras configuraciones del sistema')
                    ->schema([
                        Forms\Components\KeyValue::make('settings')
                            ->label('Configuraciones JSON')
                            ->keyLabel('Clave')
                            ->valueLabel('Valor')
                            ->helperText('Configuraciones adicionales en formato clave-valor')
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),

                Forms\Components\Section::make('Información de correos')
                    ->description('Informacion correos de la empresa')
                    ->schema([
                          Forms\Components\FileUpload::make('signature_image')
                            ->label('Imagen de la Firma')
                            ->image()
                            // ->imageEditor()
                            // ->imageCropAspectRatio('16:9')
                            // ->imageResizeTargetWidth('1920')
                            // ->imageResizeTargetHeight('1080')
                            ->directory('company-logos')
                            ->visibility('public')
                            ->helperText('Formatos: PNG, JPG, SVG. Tamaño máximo: 2MB'),
                    ]),

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
                                    ->placeholder('Ej: https://www.facebook.com/resistancegym')
                                    ->maxLength(255),

                                Forms\Components\FileUpload::make('icon')
                                    ->label('Icono/Imagen')
                                    ->image()
                                    ->directory('company-logos/social-icons')
                                    ->visibility('public')
                                    ->helperText('Icono o imagen representativa de la red social')
                                    ->maxSize(1024), // 1MB máximo

                                // Forms\Components\TextInput::make('color')
                                //     ->label('Color de la Red Social')
                                //     ->placeholder('Ej: #1877F2 (Facebook), #E4405F (Instagram)')
                                //     ->helperText('Color hexadecimal para personalizar la apariencia')
                                //     ->maxLength(7),
                            ])
                            ->columns(2)
                            ->itemLabel(fn (array $state): ?string => $state['name'] ?? 'Nueva Red Social')
                            ->addActionLabel('Agregar Red Social')
                            ->reorderable(true)
                            ->collapsible()
                            ->cloneable()
                            ->defaultItems(0)
                            ->helperText('Agrega las redes sociales de tu empresa con sus respectivos enlaces e iconos'),
                    ])
                    ->collapsible()
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
                Tables\Columns\TextColumn::make('legal_name')
                    ->label('Razón Social')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('tax_id')
                    ->label('RUC')
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
                        if (!$state || empty($state)) {
                            return 'Sin redes sociales';
                        }

                        // Asegurar que sea un array
                        if (!is_array($state)) {
                            return 'Sin redes sociales';
                        }

                        $count = count($state);
                        $names = array_column($state, 'name');
                        return $count . ' red(es): ' . implode(', ', $names);
                    })
                    ->searchable(false)
                    ->sortable(false),
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
                // No permitir acciones masivas
            ])
            ->paginated(false); // No paginar ya que solo habrá una empresa
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
