<?php

namespace App\Filament\Resources\PromoCodesResource\RelationManagers;

use App\Models\Package;
use App\Models\Discipline;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PackageDiscountsRelationManager extends RelationManager
{
    protected static string $relationship = 'packages';

    protected static ?string $title = 'Descuentos por Paquete';

    protected static ?string $modelLabel = 'Descuento de Paquete';

    protected static ?string $pluralModelLabel = 'Descuentos de Paquetes';

    protected static ?string $recordTitleAttribute = 'name';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Configuración de Descuento')
                    ->description('Asigna descuentos específicos para cada paquete')
                    ->schema([
                        Forms\Components\Select::make('package_id')
                            ->label('Paquete')
                            ->options(function () {
                                return Package::with('discipline')
                                    ->where('status', 'active')
                                    ->orderBy('name')
                                    ->get()
                                    ->mapWithKeys(function ($package) {
                                        $label = $package->name . ' - ' . ($package->discipline->name ?? 'Sin disciplina') . ' (' . $package->classes_quantity . ' clases) - S/ ' . number_format($package->price_soles, 2);
                                        return [$package->id => $label];
                                    });
                            })
                            ->required()
                            ->searchable()
                            ->preload()
                            ->live(debounce: 500)
                            ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                if ($state) {
                                    $package = Package::find($state);
                                    if ($package) {
                                        $set('original_price', $package->price_soles);
                                    }
                                }
                            }),

                        Forms\Components\TextInput::make('original_price')
                            ->label('Precio Original')
                            ->numeric()
                            ->prefix('S/')
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText('Precio actual del paquete'),

                        Forms\Components\TextInput::make('discount')
                            ->label('Descuento')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->suffix('%')
                            ->live(debounce: 1000)
                            ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                $originalPrice = $get('original_price');
                                if ($originalPrice && $state) {
                                    $discountAmount = ($originalPrice * $state) / 100;
                                    $finalPrice = $originalPrice - $discountAmount;
                                    $set('discount_amount', $discountAmount);
                                    $set('final_price', $finalPrice);
                                }
                            }),

                        Forms\Components\TextInput::make('discount_amount')
                            ->label('Monto de Descuento')
                            ->numeric()
                            ->prefix('S/')
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText('Monto que se descontará'),

                        Forms\Components\TextInput::make('final_price')
                            ->label('Precio Final')
                            ->numeric()
                            ->prefix('S/')
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText('Precio final después del descuento'),

                        Forms\Components\TextInput::make('quantity')
                            ->label('Cantidad de Códigos')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(1000)
                            ->default(1)
                            ->helperText('Cantidad de códigos promocionales disponibles para este paquete'),
                    ])
                    ->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Paquete')
                    ->getStateUsing(fn ($record) => $record->name ?? 'N/A')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('discipline.name')
                    ->label('Disciplina')
                    ->getStateUsing(fn ($record) => $record->discipline->name ?? 'N/A')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('price_soles')
                    ->label('Precio Original')
                    ->getStateUsing(fn ($record) => $record->price_soles ?? 0)
                    ->money('PEN')
                    ->sortable(),

                Tables\Columns\TextColumn::make('pivot.discount')
                    ->label('Descuento')
                    ->suffix('%')
                    ->badge()
                    ->color('success')
                    ->sortable(),

                Tables\Columns\TextColumn::make('discount_amount')
                    ->label('Monto Descuento')
                    ->getStateUsing(function ($record) {
                        $price = $record->price_soles ?? 0;
                        $discount = $record->pivot->discount ?? 0;
                        return ($price * $discount) / 100;
                    })
                    ->money('PEN')
                    ->sortable(),

                Tables\Columns\TextColumn::make('final_price')
                    ->label('Precio Final')
                    ->getStateUsing(function ($record) {
                        $price = $record->price_soles ?? 0;
                        $discount = $record->pivot->discount ?? 0;
                        $discountAmount = ($price * $discount) / 100;
                        return $price - $discountAmount;
                    })
                    ->money('PEN')
                    ->sortable()
                    ->color('success'),

                Tables\Columns\TextColumn::make('pivot.quantity')
                    ->label('Cantidad')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                Tables\Columns\TextColumn::make('pivot.created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('discipline_id')
                    ->label('Disciplina')
                    ->options(function () {
                        return Discipline::all()
                            ->mapWithKeys(function ($discipline) {
                                return [$discipline->id => $discipline->name];
                            });
                    })
                    ->searchable()
                    ->preload(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Agregar Descuento')
                    ->icon('heroicon-o-plus')
                    ->color('success')
                    ->modalHeading('Agregar Descuento por Paquete')
                    ->modalDescription('Asigna un descuento específico para un paquete con este código promocional')
                    ->modalSubmitActionLabel('Agregar Descuento')
                    ->using(function (array $data) {
                        $package = Package::find($data['package_id']);
                        if (!$package) {
                            throw new \Exception('Paquete no encontrado');
                        }

                        // Crear la relación en la tabla pivot
                        $this->getOwnerRecord()->packages()->attach($package->id, [
                            'discount' => $data['discount'],
                            'quantity' => $data['quantity'],
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        return $package;
                    })
                    ->successNotificationTitle('Descuento agregado exitosamente'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Editar')
                    ->icon('heroicon-o-pencil')
                    ->color('warning')
                    ->modalHeading('Editar Descuento por Paquete')
                    ->modalDescription('Modifica el descuento y cantidad para este paquete')
                    ->using(function ($record, array $data) {
                        $record->pivot->update([
                            'discount' => $data['discount'],
                            'quantity' => $data['quantity'],
                            'updated_at' => now(),
                        ]);
                        return $record;
                    }),

                Tables\Actions\DeleteAction::make()
                    ->label('Eliminar')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->modalHeading('Eliminar Descuento')
                    ->modalDescription('¿Estás seguro de que quieres eliminar este descuento? Esta acción no se puede deshacer.')
                    ->modalSubmitActionLabel('Sí, eliminar'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Eliminar Seleccionados'),
                ]),
            ])
            ->emptyStateHeading('Sin descuentos asignados')
            ->emptyStateDescription('Agrega descuentos específicos para paquetes con este código promocional')
            ->emptyStateIcon('heroicon-o-tag');
    }
}
