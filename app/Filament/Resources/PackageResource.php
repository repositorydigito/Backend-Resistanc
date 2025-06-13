<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PackageResource\Pages;
use App\Filament\Resources\PackageResource\RelationManagers;
use App\Models\Package;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PackageResource extends Resource
{
    protected static ?string $model = Package::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationGroup = 'Administración';

    protected static ?string $navigationLabel = 'Paquetes';

    protected static ?string $label = 'Paquete'; // Nombre en singular
    protected static ?string $pluralLabel = 'Paquetes'; // Nombre en plural

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Toggle::make('is_featured')
                    ->label('Destacado')
                    ->required(),
                // Forms\Components\Toggle::make('is_popular')
                //     ->label('Popular')
                //     ->required(),
                Section::make('Información del paquete')
                    ->columns(2)
                    ->schema([


                        Forms\Components\TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(100),

                        Forms\Components\TextInput::make('classes_quantity')
                            ->label('Cantidad de clases')
                            ->required()
                            ->numeric(),


                        Forms\Components\Select::make('discipline_id')
                            ->label('Disciplina')
                            ->relationship('discipline', 'name')
                            ->required(),

                        Forms\Components\TextInput::make('price_soles')
                            ->label('Precio con descuento')
                            ->required()
                            ->numeric(),
                        Forms\Components\TextInput::make('original_price_soles')
                            ->label('Precio base')
                            ->numeric(),
                        Forms\Components\TextInput::make('validity_days')
                            ->label('Días validos')
                            ->required()
                            ->numeric(),

                        Forms\Components\Select::make('buy_type')
                            ->options([
                                'affordable' => 'Comprable',
                                'assignable' => 'Asignable',
                            ])
                            ->label('Tipo de compra')
                            ->default('assignable')
                            ->required(),


                        Forms\Components\ColorPicker::make('color_hex')
                            ->label('Color')
                            ->required()
                            ->default('#000000'),
                        Forms\Components\Select::make('billing_type')
                            ->options([
                                'one_time' => 'Pago único',
                                'monthly' => 'Mensual',
                                'quarterly' => 'Trimestral',
                                'yearly' => 'Anual',
                            ])
                            ->label('Tipo de pago')
                            ->required(),
                        // Forms\Components\Toggle::make('is_virtual_access')
                        //     ->required(),
                        // Forms\Components\TextInput::make('priority_booking_days')
                        //     ->required()
                        //     ->numeric()
                        //     ->default(0),
                        // Forms\Components\Toggle::make('auto_renewal')
                        //     ->required(),


                        Forms\Components\Select::make('status')
                            ->options([
                                'active' => 'Activo',
                                'inactive' => 'Inactivo',
                                'coming_soon' => 'Próximamente',
                                'discontinued' => 'Descontinuado',
                            ])
                            ->default('active')
                            ->label('Estado')
                            ->required(),


                        Forms\Components\TextInput::make('display_order')
                            ->label('Orden de visualización')
                            ->unique(ignoreRecord: true)
                            ->required()
                            ->numeric()
                            ->helperText(function () {
                                $existingOrders = \App\Models\Package::orderBy('display_order')
                                    ->pluck('display_order')
                                    ->filter()
                                    ->unique()
                                    ->values()
                                    ->toArray();

                                $nextAvailable = (max($existingOrders) ?? 0) + 1;

                                return "Órdenes ya usados: " . implode(', ', $existingOrders) .
                                    ". Siguiente disponible: {$nextAvailable}";
                            })
                            ->default(function () {
                                return (\App\Models\Package::max('display_order') ?? 0) + 1;
                            }),
                        // Forms\Components\TextInput::make('features'),
                        // Forms\Components\TextInput::make('restrictions'),
                        Forms\Components\Select::make('target_audience')
                            ->label('Audiencia objetivo')
                            ->options([
                                'beginner' => 'Principiantes',
                                'intermediate' => 'Intermedios',
                                'advanced' => 'Avanzados',
                                'all' => 'Todos',
                            ])
                            ->required(),
                        Forms\Components\Select::make('type')
                            ->live() // Hace que el campo sea reactivo
                            ->options([
                                'fixed' => 'Fijo',
                                'temporary' => 'Temporal',
                            ])
                            ->label('Tipo de paquete')
                            ->required(),

                        Forms\Components\Select::make('commercial_type')
                            ->options([
                                'promotion' => 'Promoción',
                                'offer' => 'Oferta',
                                'basic' => 'Básico',
                            ])
                            ->label('Comercial')
                            ->required(),
                        Forms\Components\Select::make('mode_type')
                            ->options([
                                'presencial' => 'Presencial',
                                'virtual' => 'Virtual',
                                'mixto' => 'Mixto',
                            ])
                            ->label('Modalidad')
                            ->required(),
                        Forms\Components\DatePicker::make('start_date')
                            ->visible(fn($get) => $get('type') !== 'fixed') // Visible cuando NO es fijo
                            ->label('Fecha de inicio')
                            ->required(fn($get) => $get('type') !== 'fixed'), // Requerido cuando NO es fijo

                        Forms\Components\DatePicker::make('end_date')
                            ->visible(fn($get) => $get('type') !== 'fixed') // Visible cuando NO es fijo
                            ->label('Fecha de fin')
                            ->required(fn($get) => $get('type') !== 'fixed'), // Requerido cuando NO es fijo


                        Forms\Components\TextArea::make('short_description')
                            ->label('Descripción corta')
                            ->columnSpanFull()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->label('Descripción')
                            ->required()
                            ->columnSpanFull(),
                        Forms\Components\Section::make('Membresía')
                            ->schema([
                                Forms\Components\Toggle::make('membreship')
                                    ->label('¿Tiene membresia?')
                                    ->live()
                                    ->default(function ($record) {
                                        // Si estamos editando y hay membership_id, marcar como true
                                        return $record ? !empty($record->membership_id) : false;
                                    })
                                    ->afterStateHydrated(function ($set, $get, $record) {
                                        // Al cargar el formulario, marcar si hay membership_id
                                        if ($record && $record->membership_id) {
                                            $set('membreship', true);
                                        }
                                    }),

                                Forms\Components\Select::make('membership_id')
                                    ->visible(fn($get) => $get('membreship'))
                                    ->live()
                                    ->label('Membresía')
                                    ->relationship('membership', 'name')
                                    ->columnSpanFull(),
                            ]),

                    ])
            ]);
    }

    public static function table(Table $table): Table
    {

        if (!function_exists('getContrastColor')) {
            function getContrastColor($hexcolor)
            {
                $hexcolor = ltrim($hexcolor, '#');
                if (strlen($hexcolor) !== 6) return '#ffffff';

                $r = hexdec(substr($hexcolor, 0, 2));
                $g = hexdec(substr($hexcolor, 2, 2));
                $b = hexdec(substr($hexcolor, 4, 2));
                $yiq = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;
                return ($yiq >= 128) ? '#000000' : '#ffffff';
            }
        }
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable(),

                // Tables\Columns\TextColumn::make('short_description')
                //     ->searchable(),

                Tables\Columns\TextColumn::make('classes_quantity')
                    ->label('N° de Clases')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('buy_type')
                    ->label('Tipo de compra')
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'affordable' => 'Comprable',
                        'assignable' => 'Asignable',
                        default => ucfirst($state),
                    })
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'affordable' => 'success',
                        'assignable' => 'warning',
                        default => 'gray',
                    })
                    ->searchable(),

                Tables\Columns\TextColumn::make('membership.name')
                    ->label('Membresía')
                    ->searchable(),


                Tables\Columns\TextColumn::make('discipline.name')
                    ->label('Disciplina')
                    ->badge()
                    ->color(fn($record) => $record->discipline->color_hex)
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'cycling' => 'Ciclo',
                        'solidreformer' => 'Reformer',
                        'pilates_mat' => 'Pilates Mat',
                        default => ucfirst($state),
                    })
                    ->searchable()
                    ->extraAttributes(function ($record) {
                        return [
                            'style' => "background-color: {$record->discipline->color_hex}10; color:  {$record->discipline->color_hex}; border: 1px solid {$record->discipline->color_hex}; padding: 0; font-weight: bold; border-radius: 0.45rem; text-align: center; display: flex; width: 100%; justify-content: center; align-items: center;",
                            'class' => 'p-0 text-sm text-center' // Estilos adicionales para el badge
                        ];
                    }),


                // Tables\Columns\TextColumn::make('price_soles')
                //     ->label('Precio con descuento')
                //     ->numeric()
                //     ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Tipo de paquete')
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'fixed' => 'Fijo',
                        'temporary' => 'Temporal',
                        default => ucfirst($state),
                    })
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'fixed' => 'success',
                        'temporary' => 'warning',
                        default => 'gray',
                    })
                    ->searchable(),
                Tables\Columns\TextColumn::make('start_date')
                    ->label('Fecha de inicio')
                    ->date('M d') // Ej: "Ene 15", "Feb 28"
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->label('Fecha de fin')
                    ->date('M d') // Ej: "Ene 15", "Feb 28"
                    ->sortable(),

                Tables\Columns\TextColumn::make('original_price_soles')
                    ->label('Precio Base')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('validity_days')
                    ->label('Días Validos')
                    ->numeric()
                    ->sortable(),
                // Tables\Columns\TextColumn::make('package_type')
                //     ->label('Tipo de paquete'), // Permite renderizar HTML
                // Tables\Columns\TextColumn::make('billing_type'),
                // Tables\Columns\IconColumn::make('is_virtual_access')
                //     ->boolean(),
                // Tables\Columns\TextColumn::make('priority_booking_days')
                //     ->numeric()
                //     ->sortable(),
                // Tables\Columns\IconColumn::make('auto_renewal')
                //     ->boolean(),
                // Tables\Columns\IconColumn::make('is_featured')
                //     ->boolean(),
                // Tables\Columns\IconColumn::make('is_popular')
                //     ->boolean(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'active' => 'Activo',
                        'inactive' => 'Inactivo',
                        default => 'Desconocido',
                    })
                    ->color(fn(string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'danger',
                        default => 'secondary',
                    }),
                // Tables\Columns\TextColumn::make('display_order')
                //     ->numeric()
                //     ->sortable(),
                // Tables\Columns\TextColumn::make('target_audience'),
                // Tables\Columns\TextColumn::make('created_at')
                //     ->dateTime()
                //     ->sortable()
                //     ->toggleable(isToggledHiddenByDefault: true),
                // Tables\Columns\TextColumn::make('updated_at')
                //     ->dateTime()
                //     ->sortable()
                //     ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
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
            'index' => Pages\ListPackages::route('/'),
            'create' => Pages\CreatePackage::route('/create'),
            'edit' => Pages\EditPackage::route('/{record}/edit'),
        ];
    }
}
