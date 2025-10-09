<?php

namespace App\Filament\Resources\UserProfileResource\RelationManagers;

use App\Models\Package;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\DB;
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
                Forms\Components\Select::make('package_id')
                    ->label('Paquete Free Trial')
                    ->relationship(
                        name: 'package',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn(Builder $query) => $query
                            ->where('status', 'active')
                            ->where('type', 'free_trial')
                            ->orderBy('name')
                            ->with('disciplines')
                    )
                    ->getOptionLabelFromRecordUsing(
                        fn(Package $record) =>
                        $record->name . ' - ' . ($record->disciplines->pluck('name')->join(', ') ?: 'Sin disciplina') . ' (' . $record->classes_quantity . ' clases)'
                    )
                    ->required()
                    ->searchable()
                    ->preload()
                    ->helperText('Solo se muestran paquetes de tipo Free Trial')
                    ->reactive()
                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                        if ($state) {
                            $this->updatePackageFields($state, $set, $get);
                        }
                    }),
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

                Tables\Columns\TextColumn::make('package.disciplines')
                    ->label('Disciplinas')
                    ->getStateUsing(fn($record) => $record->package?->disciplines->pluck('name')->join(', ') ?: 'N/A')
                    ->badge()
                    ->color('primary')
                    ->wrap(),

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

                Tables\Columns\TextColumn::make('promo_code_used')
                    ->label('Código Promo')
                    ->badge()
                    ->color('success')
                    ->placeholder('—')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('discount_percentage')
                    ->label('Descuento')
                    ->suffix('%')
                    ->badge()
                    ->color('warning')
                    ->placeholder('—')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('original_package_price_soles')
                    ->label('Precio Original')
                    ->money('PEN')
                    ->getStateUsing(fn($record) => $record->original_package_price_soles ?? $record->package?->price_soles)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('real_amount_paid_soles')
                    ->label('Monto Real Pagado')
                    ->money('PEN')
                    ->sortable()
                    ->weight('bold')
                    ->color(fn($record) => $record->promo_code_used ? 'success' : null)
                    ->getStateUsing(fn($record) => $record->real_amount_paid_soles ?? $record->amount_paid_soles),

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

                Tables\Filters\Filter::make('with_promo_code')
                    ->label('Con Código Promocional')
                    ->query(fn(Builder $query) => $query->whereNotNull('promo_code_used'))
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\DeleteAction::make()
                    ->label('Eliminar')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->modalHeading('Eliminar Paquete Free Trial')
                    ->modalDescription('¿Estás seguro de que quieres eliminar este paquete Free Trial? Esta acción no se puede deshacer.')
                    ->modalSubmitActionLabel('Sí, eliminar')
                    ->visible(fn($record) => $record->package && $record->package->type === 'free_trial'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([

                ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Asignar Paquete Free Trial')
                    ->icon('heroicon-o-gift')
                    ->color('success')
                    ->modalHeading('Asignar Paquete Free Trial')
                    ->modalDescription('Selecciona un paquete de tipo Free Trial para asignar al cliente')
                    ->modalSubmitActionLabel('Asignar Paquete')
                    ->createAnother(false) // Deshabilitar "Crear y crear otro"
                    ->using(function (array $data): \App\Models\UserPackage {
                        // Verificar que el paquete seleccionado sea free_trial
                        $package = Package::find($data['package_id']);
                        if ($package && $package->type !== 'free_trial') {
                            throw new \Exception('Solo se pueden asignar paquetes de tipo Free Trial');
                        }

                        // Obtener el usuario del perfil desde el contexto del RelationManager
                        $userProfile = $this->getOwnerRecord();
                        $user = $userProfile->user;
                        if (!$user) {
                            throw new \Exception('No se encontró el usuario asociado al perfil');
                        }

                        // Calcular fecha de expiración
                        $expiryDate = $package->duration_in_months
                            ? now()->addMonths($package->duration_in_months)
                            : now()->addDays($package->validity_days ?? 30);

                        // Crear el UserPackage directamente
                        return \App\Models\UserPackage::create([
                            'user_id' => $user->id,
                            'package_id' => $package->id,
                            'remaining_classes' => $package->classes_quantity,
                            'used_classes' => 0,
                            'amount_paid_soles' => 0,
                            'currency' => 'PEN',
                            'purchase_date' => now(),
                            'activation_date' => now(),
                            'expiry_date' => $expiryDate,
                            'status' => 'active',
                            'notes' => 'Paquete Free Trial asignado por administrador',
                        ]);
                    })
                    ->successNotificationTitle('Paquete Free Trial asignado exitosamente'),
            ])
            ->defaultSort('purchase_date', 'desc');
    }

    protected function updatePackageFields($packageId, Forms\Set $set, Forms\Get $get): void
    {
        $package = Package::find($packageId);
        if (!$package) return;

        // Configurar automáticamente todos los campos para paquete free_trial
        $set('remaining_classes', $package->classes_quantity);
        $set('used_classes', 0);
        $set('amount_paid_soles', 0); // Free trial es gratuito
        $set('currency', 'PEN');
        $set('purchase_date', now()->toDateString());
        $set('activation_date', now()->toDateString());
        $set('status', 'active');
        $set('notes', 'Paquete Free Trial asignado por administrador');

        // Calcular fecha de expiración
        $expiryDate = $package->duration_in_months
            ? now()->addMonths($package->duration_in_months)
            : now()->addDays($package->validity_days ?? 30);

        $set('expiry_date', $expiryDate->toDateString());
    }
}
