<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PackageResource\Pages;
use App\Filament\Resources\PackageResource\RelationManagers;
use App\Models\Package;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PackageResource extends Resource
{
    protected static ?string $model = Package::class;

    protected static ?string $navigationIcon = 'heroicon-o-fire';

    protected static ?string $navigationGroup = 'Configuración General';

    protected static ?string $navigationLabel = 'Paquetes';

    protected static ?string $label = 'Paquete de clases'; // Nombre en singular
    protected static ?string $pluralLabel = 'Paquetes de clases'; // Nombre en plural

    protected static ?int $navigationSort = 20;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([


                Section::make('Información del paquete')
                    ->columns(2)
                    ->schema([
                        // Sección 1: Información básica
                        Section::make('Datos básicos')
                            ->columns(2)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Nombre')
                                    ->required()
                                    ->maxLength(100),



                                Forms\Components\Select::make('disciplines') // Cambia a plural
                                    ->label('Disciplinas')
                                    ->relationship('disciplines', 'name') // Relación muchos a muchos
                                    ->multiple() // Permite selección múltiple
                                    ->preload() // Carga opciones anticipadamente (opcional)
                                    ->required(),

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

                                        $nextAvailable = !empty($existingOrders) ? (max($existingOrders) + 1) : 1;

                                        $ordersText = !empty($existingOrders)
                                            ? implode(', ', $existingOrders)
                                            : 'Ninguno';

                                        return "Órdenes ya usados: {$ordersText}. Siguiente disponible: {$nextAvailable}";
                                    })
                                    ->default(function () {
                                        return (\App\Models\Package::max('display_order') ?? 0) + 1;
                                    }),
                            ]),

                        // Sección 2: Precios y duración
                        Section::make('Precios y validez')
                            ->columns(2)
                            ->schema([
                                Forms\Components\TextInput::make('igv')
                                    ->label('IGV (%)')
                                    ->numeric()
                                    ->default(18.00)
                                    ->required()
                                    ->suffix('%')
                                    ->live()
                                    ->helperText('Impuesto General a las Ventas'),

                                Forms\Components\TextInput::make('price_with_igv')
                                    ->label('Precio de venta (con IGV)')
                                    ->required()
                                    ->numeric()
                                    ->live()
                                    ->helperText('Ingrese el precio que verá el cliente (incluye IGV)')
                                    ->afterStateHydrated(function ($set, $get, $record) {
                                        // Al cargar el registro, mostrar el precio con IGV
                                        if ($record && $record->price_soles) {
                                            $igv = (float)($record->igv ?? 18.00);
                                            $priceWithIgv = $record->price_soles * (1 + ($igv / 100));
                                            $set('price_with_igv', round($priceWithIgv, 2));
                                        }
                                    }),

                                Forms\Components\Placeholder::make('price_without_igv')
                                    ->label('Precio base (sin IGV) - Se enviará a Stripe')
                                    ->content(function ($get) {
                                        $priceWithIgv = (float)($get('price_with_igv') ?? 0);
                                        if ($priceWithIgv <= 0) {
                                            return '-';
                                        }
                                        $igv = (float)($get('igv') ?? 18.00);
                                        $priceWithoutIgv = $priceWithIgv / (1 + ($igv / 100));
                                        return 'S/ ' . number_format($priceWithoutIgv, 2, '.', ',');
                                    })
                                    ->dehydrated(false),

                                Forms\Components\TextInput::make('original_price_with_igv')
                                    ->label('Precio original (con IGV)')
                                    ->numeric()
                                    ->live()
                                    ->helperText('Precio original para mostrar descuentos (incluye IGV)')
                                    ->afterStateHydrated(function ($set, $get, $record) {
                                        // Al cargar el registro, mostrar el precio original con IGV
                                        if ($record && $record->original_price_soles) {
                                            $igv = (float)($record->igv ?? 18.00);
                                            $priceWithIgv = $record->original_price_soles * (1 + ($igv / 100));
                                            $set('original_price_with_igv', round($priceWithIgv, 2));
                                        }
                                    }),

                                Forms\Components\Placeholder::make('original_price_without_igv')
                                    ->label('Precio original (sin IGV)')
                                    ->content(function ($get) {
                                        $originalPriceWithIgv = (float)($get('original_price_with_igv') ?? 0);
                                        if ($originalPriceWithIgv <= 0) {
                                            return '-';
                                        }
                                        $igv = (float)($get('igv') ?? 18.00);
                                        $priceWithoutIgv = $originalPriceWithIgv / (1 + ($igv / 100));
                                        return 'S/ ' . number_format($priceWithoutIgv, 2, '.', ',');
                                    })
                                    ->dehydrated(false),

                                Forms\Components\Placeholder::make('net_profit')
                                    ->label('Ganancia neta (después de comisión Stripe)')
                                    ->content(function ($get) {
                                        $priceWithIgv = (float)($get('price_with_igv') ?? 0);
                                        if ($priceWithIgv <= 0) {
                                            return '-';
                                        }

                                        // Obtener la comisión de Stripe desde la tabla companies
                                        $company = \App\Models\Company::first();
                                        $stripeCommission = $company ? (float)($company->stripe_commission_percentage ?? 3.60) : 3.60;

                                        $igv = (float)($get('igv') ?? 18.00);
                                        $priceWithoutIgv = $priceWithIgv / (1 + ($igv / 100));

                                        // Calcular la comisión de Stripe sobre el precio sin IGV
                                        $stripeFee = $priceWithoutIgv * ($stripeCommission / 100);

                                        // Ganancia neta = precio sin IGV - comisión de Stripe
                                        $netProfit = $priceWithoutIgv - $stripeFee;

                                        return 'S/ ' . number_format($netProfit, 2, '.', ',');
                                    })
                                    ->extraAttributes(['class' => 'font-bold text-success-600']),

                                Forms\Components\TextInput::make('duration_in_months')
                                    ->label('Vigencia en meses')
                                    ->numeric()
                                    ->default(0)
                                    ->required(fn($get) => $get('type') !== 'fixed'),
                            ]),

                        // Sección 3: Configuración comercial
                        Section::make('Configuración comercial')
                            ->columns(2)
                            ->schema([

                                Forms\Components\TextInput::make('classes_quantity')
                                    ->label('Cantidad de clases')
                                    ->required()
                                    ->numeric(),

                                Forms\Components\Select::make('buy_type')
                                    ->options([
                                        'affordable' => 'Comprable',
                                        'assignable' => 'Asignable',
                                    ])
                                    ->label('Tipo de compra')
                                    ->default('affordable')
                                    ->required(),

                                Forms\Components\Select::make('commercial_type')
                                    ->options([
                                        'promotion' => 'Promoción',
                                        'offer' => 'Oferta',
                                        'basic' => 'Básico',
                                    ])
                                    ->default('basic')
                                    ->label('Tipo comercial')
                                    ->required(),

                                Forms\Components\Toggle::make('is_membresia')
                                    ->label('¿Es membresía?')
                                    ->live()
                                    ->default(false)
                                    ->helperText('Si es true, será un pago recurrente'),

                                Forms\Components\TextInput::make('recurrence_months')
                                    ->label('Meses de recurrencia')
                                    ->numeric()
                                    ->default(1)
                                    ->visible(fn($get) => $get('is_membresia') === true)
                                    ->required(fn($get) => $get('is_membresia') === true)
                                    ->helperText('Cada cuántos meses se renovará automáticamente'),

                            ]),

                        // Sección 4: Configuración de fechas
                        Section::make('Configuración de fechas (para paquetes temporales)')
                            ->columns(2)
                            ->schema([
                                Forms\Components\Select::make('type')
                                    ->live()
                                    ->options([
                                        'free_trial' => 'Prueba Gratis',
                                        'fixed' => 'Fijo',
                                        'temporary' => 'Temporal',
                                    ])
                                    ->label('Tipo de paquete')
                                    ->default('fixed')
                                    ->required(),

                                Forms\Components\DatePicker::make('start_date')
                                    ->live()
                                    ->visible(fn($get) => $get('type') == 'temporary')
                                    ->label('Fecha de inicio')
                                    ->required(fn($get) => $get('type') !== 'fixed'),

                                Forms\Components\DatePicker::make('end_date')
                                    ->live()
                                    ->visible(fn($get) => $get('type') == 'temporary')
                                    ->label('Fecha de fin')
                                    ->required(fn($get) => $get('type') !== 'fixed'),
                            ]),

                        // Sección 5: Multimedia y diseño
                        Section::make('Multimedia y diseño')
                            ->columns(2)
                            ->schema([
                                Forms\Components\FileUpload::make('icon_url')
                                    ->label('Icono')
                                    ->disk('public')
                                    ->directory('packages/icons')
                                    ->visibility('public')
                                    ->acceptedFileTypes(['image/*'])
                                    ->maxSize(1024 * 5)
                                    ->imageResizeMode('crop')
                                    ->imageResizeTargetWidth(800)
                                    ->imageResizeTargetHeight(600)
                                    ->image(),

                                Forms\Components\ColorPicker::make('color_hex')
                                    ->label('Color')
                                    ->required()
                                    ->default('#000000'),
                            ]),

                        // Sección 6: Descripciones
                        Section::make('Descripciones')
                            ->schema([
                                Textarea::make('short_description')
                                    ->label('Descripción corta')
                                    ->columnSpanFull()
                                    ->maxLength(255),

                                Textarea::make('description')
                                    ->label('Descripción')
                                    ->required()
                                    ->columnSpanFull(),
                            ]),

                        // Sección 7: Membresía
                        Section::make('Configuración de membresía')
                            ->schema([
                                Forms\Components\Toggle::make('membreship')
                                    ->label('¿Incluye membresía?')
                                    ->live()
                                    ->default(function ($record) {
                                        return $record ? !empty($record->membership_id) : false;
                                    })
                                    ->afterStateHydrated(function ($set, $get, $record) {
                                        if ($record && $record->membership_id) {
                                            $set('membreship', true);
                                        }
                                    })
                                    ->afterStateUpdated(function ($set, $state, $record) {
                                        // Si se desmarca el toggle, limpiar la membresía
                                        if (!$state) {
                                            $set('membership_id', null);

                                            // Si estamos editando un registro existente, actualizar el modelo
                                            if ($record && $record->exists) {
                                                $record->update(['membership_id' => null]);
                                            }
                                        }
                                    }),

                                Forms\Components\Select::make('membership_id')
                                    ->visible(fn($get) => $get('membreship'))
                                    ->live()
                                    ->label('Categoria asociada')
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

                Tables\Columns\TextColumn::make('id')
                    ->label('Id')
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_membresia')
                    ->label('Pago Recurrente')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-x-mark')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable(),

                Tables\Columns\TextColumn::make('classes_quantity')
                    ->label('N° de Clases')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('duration_in_months')
                    ->label('Duración en meses')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('buy_type')
                    ->label('Tipo de compra')
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'affordable' => 'Compra',
                        'assignable' => 'Asignación',
                        default => ucfirst($state),
                    })
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'affordable' => 'success',
                        'assignable' => 'warning',
                        default => 'gray',
                    })
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('membership.name')
                    ->label('Categoria')
                    ->searchable(),


                Tables\Columns\TextColumn::make('disciplines')
                    ->label('Disciplinas')
                    ->badge()
                    ->color('primary')
                    ->getStateUsing(function ($record) {
                        return $record->disciplines->pluck('name')->map(function ($name) {
                            return match ($name) {
                                'cycling' => 'Ciclo',
                                'solidreformer' => 'Reformer',
                                'pilates_mat' => 'Pilates Mat',
                                default => ucfirst($name),
                            };
                        })->join(', ');
                    })
                    ->searchable(query: function ($query, $search) {
                        $query->whereHas('disciplines', function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%");
                        });
                    }),

                Tables\Columns\TextColumn::make('type')
                    ->label('Tipo de paquete')
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'free_trial' => 'Prueba Gratis',
                        'fixed' => 'Fijo',
                        'temporary' => 'Temporal',
                        default => ucfirst($state),
                    })
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'free_trial' => 'danger',
                        'fixed' => 'success',
                        'temporary' => 'warning',
                        default => 'gray',
                    })
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('start_date')
                    ->label('Período de validez')
                    ->formatStateUsing(function ($record) {
                        $startDate = \Carbon\Carbon::parse($record->start_date)->format('M d');
                        $endDate = \Carbon\Carbon::parse($record->end_date)->format('M d');
                        return "{$startDate} - {$endDate}";
                    })
                    ->sortable(),


                Tables\Columns\TextColumn::make('original_price_soles')
                    ->label('Precio Base')
                    ->numeric()
                    ->sortable(),

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

            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'active' => 'Activo',
                        'inactive' => 'Inactivo',
                        'coming_soon' => 'Próximamente',
                        'discontinued' => 'Descontinuado',
                    ])
                    ->default('active'),
                Tables\Filters\SelectFilter::make('buy_type')
                    ->label('Tipo de compra')
                    ->options([
                        'affordable' => 'Comprable',
                        'assignable' => 'Asignable',
                    ]),
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipo de paquete')
                    ->options([
                        'free_trial' => 'Prueba gratis',
                        'fixed' => 'Fijo',
                        'temporary' => 'Temporal',
                    ]),
                Tables\Filters\SelectFilter::make('commercial_type')
                    ->label('Comercial')
                    ->options([
                        'promotion' => 'Promoción',
                        'offer' => 'Oferta',
                        'basic' => 'Básico',
                    ]),
                Tables\Filters\SelectFilter::make('mode_type')
                    ->label('Modo')
                    ->options([
                        'presencial' => 'Presencial',
                        'virtual' => 'Virtual',
                        'mixto' => 'Mixto',
                    ]),

                Tables\Filters\SelectFilter::make('disciplines') // Cambiar a plural
                    ->label('Disciplina')
                    ->relationship('disciplines', 'name') // Relación muchos a muchos
                    ->preload() // Opcional: cargar opciones anticipadamente
                    ->multiple(), // Permitir filtrar por múltiples disciplinas
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
