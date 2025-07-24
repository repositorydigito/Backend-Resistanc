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
    protected static ?string $title = 'Paquetes del Usuario';
    protected static ?string $modelLabel = 'Paquete';
    protected static ?string $pluralModelLabel = 'Paquetes';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                // Sección 1: Información del paquete
                Forms\Components\Section::make('Información del paquete')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('package_id')
                            ->label('Paquete')
                            ->relationship(
                                name: 'package',
                                titleAttribute: 'name', // Mostrará solo el nombre inicialmente
                                modifyQueryUsing: fn(Builder $query) => $query
                                    ->where('status', 'active')
                                    ->orderBy('discipline_id')
                                    ->with('discipline') // Carga eager loading de la relación
                            )
                            ->getOptionLabelFromRecordUsing(
                                fn(Package $record) =>
                                $record->name . ' - ' . ($record->discipline->name ?? 'Sin disciplina')
                            )
                            ->required()
                            ->searchable()
                            ->preload(),

                        Forms\Components\TextInput::make('remaining_classes')
                            ->label('Restantes')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->disabled(),


                    ]),

                // Sección 2: Clases y pagos
                Forms\Components\Section::make('Clases y pago')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([


                                // Forms\Components\TextInput::make('used_classes')
                                //     ->label('Usadas')
                                //     ->required()
                                //     ->numeric()
                                //     ->minValue(0)
                                //     ->reactive(),


                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('amount_paid_soles')
                                    ->label('Monto pagado (S/.)')
                                    ->required()
                                    ->numeric()
                                    ->prefix('S/.')
                                    ->step(0.01)
                                    ->disabled(true)
                                    ->minValue(0),

                                Forms\Components\Select::make('currency')
                                    ->label('Moneda')
                                    ->options([
                                        'PEN' => 'Soles (S/.)',
                                        'USD' => 'Dólares ($)',
                                        'EUR' => 'Euros (€)',
                                    ])
                                    ->default('PEN')
                                    ->disabled(true)
                                    ->required(),
                            ]),
                    ]),

                // Sección 3: Fechas y estado
                Forms\Components\Section::make('Fechas y estado')
                    ->columns(3)
                    ->schema([
                        Forms\Components\DatePicker::make('purchase_date')
                            ->label('Compra')
                            ->required()
                            ->default(now())
                            ->maxDate(now())
                            ->reactive()
                            ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                $packageId = $get('package_id');
                                if ($packageId) {
                                    $this->updatePackageFields($packageId, $set, $get, $state);
                                }
                            }),

                        Forms\Components\DatePicker::make('activation_date')
                            ->label('Activación')
                            ->nullable()
                            ->afterOrEqual('purchase_date'),

                        Forms\Components\DatePicker::make('expiry_date')
                            ->label('Expiración')
                            ->required()
                            ->after('purchase_date')
                            ->disabled(),

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
                            ->required(),



                        Forms\Components\TextInput::make('renewal_price')
                            ->label('Precio renovación')
                            ->numeric()
                            ->prefix('S/.')
                            ->step(0.01)
                            ->minValue(0)
                            ->nullable(),
                    ]),

                // Sección 4: Información adicional
                Forms\Components\Section::make('Información adicional')
                    ->schema([


                        Forms\Components\Textarea::make('notes')
                            ->label('Notas')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
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
                    ->copyable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('package.name')
                    ->label('Paquete')
                    ->description(fn($record) => $record->package->short_description ?? '')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('package.discipline.name')
                    ->label('Disciplina')
                    ->badge()
                    ->color('primary')
                    ->sortable(),

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
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('remaining_classes')
                    ->label('Clases')
                    // ->formatStateUsing(fn($state, $record) => "{$state}/{$record->total_classes}")
                    ->badge()
                    ->color(fn(int $state): string => match (true) {
                        $state > 10 => 'success',
                        $state > 5 => 'warning',
                        $state > 0 => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('expiry_status')
                    ->label('Expiración')
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'Vencido' => 'danger',
                        'Por vencer' => 'warning',
                        'Vigente' => 'success',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('amount_paid_soles')
                    ->label('Monto')
                    ->money('PEN')
                    ->sortable(),

                Tables\Columns\TextColumn::make('purchase_date')
                    ->label('Compra')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('expiry_date')
                    ->label('Expira')
                    ->date()
                    ->sortable(),
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
                    ->label('Por expirar (7 días)')
                    ->query(fn(Builder $query) => $query
                        ->where('expiry_date', '<=', now()->addDays(7))
                        ->where('expiry_date', '>=', now())
                        ->where('status', 'active')),


            ])
            ->actions([
                Tables\Actions\EditAction::make(),

                Tables\Actions\Action::make('activate')
                    ->label('Activar')
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->action(fn($record) => $record->update([
                        'status' => 'active',
                        'activation_date' => now(),
                    ]))
                    ->visible(fn($record) => $record->status === 'pending'),

                Tables\Actions\Action::make('suspend')
                    ->label('Suspender')
                    ->icon('heroicon-o-pause')
                    ->color('warning')
                    ->action(fn($record) => $record->update(['status' => 'suspended']))
                    ->visible(fn($record) => $record->status === 'active'),

                Tables\Actions\Action::make('renew')
                    ->label('Renovar')
                    ->icon('heroicon-o-arrow-path')
                    ->color('info')
                    ->form([
                        Forms\Components\DatePicker::make('new_expiry_date')
                            ->label('Nueva fecha')
                            ->required()
                            ->default(fn($record) => $record->expiry_date->addDays($record->package->validity_days ?? 30)),

                        Forms\Components\TextInput::make('renewal_amount')
                            ->label('Monto')
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
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),

                    Tables\Actions\BulkAction::make('activate_selected')
                        ->label('Activar seleccionados')
                        ->icon('heroicon-o-play')
                        ->color('success')
                        ->action(fn($records) => $records->each->update([
                            'status' => 'active',
                            'activation_date' => now(),
                        ])),
                ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Asignar paquete'),
            ])
            ->defaultSort('purchase_date', 'desc');
    }

    protected function updatePackageFields($packageId, Forms\Set $set, Forms\Get $get, $purchaseDate = null): void
    {
        $package = Package::find($packageId);
        if (!$package) return;

        $purchaseDate = $purchaseDate ? \Carbon\Carbon::parse($purchaseDate) : now();


        $set('remaining_classes', $package->classes_quantity);
        $set('used_classes', 0);
        $set('amount_paid_soles', $package->price_soles);
        $set('currency', 'PEN');

        $expiryDate = $package->duration_in_months
            ? $purchaseDate->copy()->addMonths($package->duration_in_months)
            : $purchaseDate->copy()->addDays($package->validity_days ?? 30);

        $set('expiry_date', $expiryDate->toDateString());
        // $set('package_code', strtoupper(Str::random(12)));
    }
}
