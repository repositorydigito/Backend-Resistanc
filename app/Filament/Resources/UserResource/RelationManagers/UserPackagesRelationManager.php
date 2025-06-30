<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use App\Models\Package;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

use Illuminate\Support\Str;

class UserPackagesRelationManager extends RelationManager
{
    protected static string $relationship = 'userPackages';

    protected static ?string $title = 'Paquetes de Usuario';
    protected static ?string $modelLabel = 'Paquete de Usuario';
    protected static ?string $pluralModelLabel = 'Paquetes de Usuario';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('package_id')
                    ->label('Paquete')
                    ->relationship('package', 'name')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->reactive()
                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                        if ($state) {
                            $package = Package::find($state);
                            if ($package) {
                                // Auto-completar campos basados en el paquete seleccionado
                                $set('total_classes', $package->classes_quantity);
                                $set('remaining_classes', $package->classes_quantity);
                                $set('used_classes', 0);
                                $set('amount_paid_soles', $package->price_soles);
                                $set('currency', 'PEN');

                                // Calcular fecha de expiración
                                $purchaseDate = $get('purchase_date') ? \Carbon\Carbon::parse($get('purchase_date')) : now();
                                if ($package->duration_in_months) {
                                    $expiryDate = $purchaseDate->copy()->addMonths($package->duration_in_months);
                                } elseif ($package->validity_days) {
                                    $expiryDate = $purchaseDate->copy()->addDays($package->validity_days);
                                } else {
                                    $expiryDate = $purchaseDate->copy()->addDays(30);
                                }
                                $set('expiry_date', $expiryDate->toDateString());

                                // Generar código único del paquete
                                $packageCode = strtoupper(Str::random(12));
                                $set('package_code', $packageCode);
                            }
                        }
                    }),

                Forms\Components\TextInput::make('package_code')
                    ->label('Código del Paquete')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(20)
                    ->placeholder('Se genera automáticamente')
                    ->helperText('Código único del paquete comprado'),

                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\TextInput::make('total_classes')
                            ->label('Total de Clases')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->default(1),

                        Forms\Components\TextInput::make('used_classes')
                            ->label('Clases Usadas')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->reactive()
                            ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                                $totalClasses = $get('total_classes') ?? 0;
                                $usedClasses = $state ?? 0;
                                $set('remaining_classes', max(0, $totalClasses - $usedClasses));
                            }),

                        Forms\Components\TextInput::make('remaining_classes')
                            ->label('Clases Restantes')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->readOnly(),
                    ]),

                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\TextInput::make('amount_paid_soles')
                            ->label('Monto Pagado')
                            ->required()
                            ->numeric()
                            ->prefix('S/.')
                            ->step(0.01)
                            ->minValue(0),

                        Forms\Components\Select::make('currency')
                            ->label('Moneda')
                            ->options([
                                'PEN' => 'Soles (S/.)',
                                'USD' => 'Dólares ($)',
                                'EUR' => 'Euros (€)',
                            ])
                            ->default('PEN')
                            ->required(),
                    ]),

                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\DatePicker::make('purchase_date')
                            ->label('Fecha de Compra')
                            ->required()
                            ->default(now())
                            ->maxDate(now())
                            ->reactive()
                            ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                $packageId = $get('package_id');
                                if ($packageId) {
                                    $package = Package::find($packageId);
                                    if ($package) {
                                        $purchaseDate = $state ? \Carbon\Carbon::parse($state) : now();
                                        if ($package->duration_in_months) {
                                            $expiryDate = $purchaseDate->copy()->addMonths($package->duration_in_months);
                                        } elseif ($package->validity_days) {
                                            $expiryDate = $purchaseDate->copy()->addDays($package->validity_days);
                                        } else {
                                            $expiryDate = $purchaseDate->copy()->addDays(30);
                                        }
                                        $set('expiry_date', $expiryDate->toDateString());
                                    }
                                }
                            }),

                        Forms\Components\DatePicker::make('activation_date')
                            ->label('Fecha de Activación')
                            ->nullable()
                            ->afterOrEqual('purchase_date')
                            ->helperText('Deja vacío si se activa inmediatamente'),

                        Forms\Components\DatePicker::make('expiry_date')
                            ->label('Fecha de Expiración')
                            ->required()
                            ->after('purchase_date')
                            ->helperText('Se calcula automáticamente al seleccionar el paquete'),
                    ]),

                Forms\Components\Select::make('status')
                    ->label('Estado')
                    ->options([
                        'pending' => 'Pendiente',
                        'active' => 'Activo',
                        'expired' => 'Expirado',
                        'suspended' => 'Suspendido',
                        'cancelled' => 'Cancelado',
                    ])
                    ->default('pending')
                    ->required()
                    ->helperText('Estado actual del paquete'),

                Forms\Components\Toggle::make('auto_renew')
                    ->label('Renovación Automática')
                    ->default(false)
                    ->helperText('¿Se renueva automáticamente al expirar?'),

                Forms\Components\TextInput::make('renewal_price')
                    ->label('Precio de Renovación')
                    ->numeric()
                    ->prefix('S/.')
                    ->step(0.01)
                    ->minValue(0)
                    ->nullable()
                    ->visible(fn(Forms\Get $get) => $get('auto_renew'))
                    ->helperText('Precio para la renovación automática'),

                Forms\Components\Textarea::make('benefits_included')
                    ->label('Beneficios Incluidos')
                    ->placeholder('Ej: Acceso a todas las clases, descuentos en productos, etc.')
                    ->rows(3)
                    ->nullable()
                    ->columnSpanFull(),

                Forms\Components\Textarea::make('notes')
                    ->label('Notas Adicionales')
                    ->placeholder('Observaciones, comentarios especiales, etc.')
                    ->rows(3)
                    ->nullable()
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('package_code')
            ->columns([
                Tables\Columns\TextColumn::make('package_code')
                    ->label('Código')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('package.name')
                    ->label('Paquete')
                    ->searchable()
                    ->sortable()
                    ->description(fn($record) => $record->package->short_description ?? ''),

                Tables\Columns\TextColumn::make('package.discipline.name')
                    ->label('Disciplina')
                    ->sortable()
                    ->searchable()
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'pending' => 'warning',
                        'active' => 'success',
                        'expired' => 'danger',
                        'suspended' => 'gray',
                        'cancelled' => 'gray',
                        default => 'primary',
                    }),

                Tables\Columns\TextColumn::make('remaining_classes')
                    ->label('Clases Restantes')
                    ->sortable()
                    ->badge()
                    ->color(fn(int $state): string => match (true) {
                        $state > 10 => 'success',
                        $state > 5 => 'warning',
                        $state > 0 => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn($state, $record) => "{$state} / {$record->total_classes}"),

                // Tables\Columns\ProgressColumn::make('progress')
                //     ->label('Progreso')
                //     ->getStateUsing(function ($record) {
                //         if ($record->total_classes <= 0) return 0;
                //         return ($record->used_classes / $record->total_classes) * 100;
                //     }),

                Tables\Columns\TextColumn::make('amount_paid_soles')
                    ->label('Monto Pagado')
                    ->money('PEN')
                    ->sortable(),

                Tables\Columns\TextColumn::make('purchase_date')
                    ->label('Compra')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('expiry_date')
                    ->label('Expira (estimado)')
                    ->date()
                    ->sortable(),





                // Tables\Columns\IconColumn::make('auto_renew')
                //     ->label('Auto Renovación')
                //     ->boolean(),

                // Tables\Columns\TextColumn::make('created_at')
                //     ->label('Creado')
                //     ->dateTime()
                //     ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'pending' => 'Pendiente',
                        'active' => 'Activo',
                        'expired' => 'Expirado',
                        'suspended' => 'Suspendido',
                        'cancelled' => 'Cancelado',
                    ]),

                Tables\Filters\Filter::make('about_to_expire')
                    ->label('Por expirar (próximos 7 días)')
                    ->query(
                        fn(Builder $query): Builder =>
                        $query->where('expiry_date', '<=', now()->addDays(7))
                            ->where('expiry_date', '>=', now())
                            ->where('status', 'active')
                    ),

                Tables\Filters\Filter::make('expired')
                    ->label('Expirados')
                    ->query(
                        fn(Builder $query): Builder =>
                        $query->where('expiry_date', '<', now())
                    ),

                Tables\Filters\TernaryFilter::make('auto_renew')
                    ->label('Renovación Automática'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Asignar Paquete'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),

                Tables\Actions\Action::make('activate')
                    ->label('Activar')
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update([
                            'status' => 'active',
                            'activation_date' => now(),
                        ]);
                    })
                    ->visible(fn($record) => $record->status === 'pending'),

                Tables\Actions\Action::make('suspend')
                    ->label('Suspender')
                    ->icon('heroicon-o-pause')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update(['status' => 'suspended']);
                    })
                    ->visible(fn($record) => $record->status === 'active'),

                Tables\Actions\Action::make('renew')
                    ->label('Renovar')
                    ->icon('heroicon-o-arrow-path')
                    ->color('info')
                    ->form([
                        Forms\Components\DatePicker::make('new_expiry_date')
                            ->label('Nueva fecha de expiración')
                            ->required()
                            ->default(fn($record) => $record->expiry_date->addDays($record->package->validity_days ?? 30)),

                        Forms\Components\TextInput::make('renewal_amount')
                            ->label('Monto de renovación')
                            ->numeric()
                            ->prefix('S/.')
                            ->default(fn($record) => $record->renewal_price ?? $record->package->price_soles),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'expiry_date' => $data['new_expiry_date'],
                            'status' => 'active',
                            'remaining_classes' => $record->package->classes_quantity,
                            'used_classes' => 0,
                        ]);
                    })
                    ->visible(fn($record) => in_array($record->status, ['expired', 'active'])),

                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),

                    Tables\Actions\BulkAction::make('bulk_activate')
                        ->label('Activar seleccionados')
                        ->icon('heroicon-o-play')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $record->update([
                                    'status' => 'active',
                                    'activation_date' => now(),
                                ]);
                            });
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
    public function getEstimatedExpiryDateAttribute()
    {
        if (! $this->package || ! $this->created_at) {
            return null;
        }

        return $this->created_at->copy()->addMonths($this->package->duration_in_months);
    }
}
