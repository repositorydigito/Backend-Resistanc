<?php

namespace App\Filament\Resources\UserProfileResource\RelationManagers;

use App\Models\UserMembership;
use App\Models\Membership;
use App\Models\Discipline;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UserMembershipsRelationManager extends RelationManager
{
    protected static string $relationship = 'userMemberships';
    protected static ?string $title = 'Membresías del Usuario';
    protected static ?string $modelLabel = 'Membresía';
    protected static ?string $pluralModelLabel = 'Membresías';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('membership_id')
                    ->label('Membresía')
                    ->relationship(
                        name: 'membership',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn(Builder $query) => $query
                            ->where('is_active', true)
                            ->where('is_benefit_discipline', true)
                            ->orderBy('name')
                    )
                    ->required()
                    ->searchable()
                    ->preload()
                    ->reactive()
                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                        if ($state) {
                            $membership = Membership::find($state);
                            if ($membership) {
                                $set('discipline_id', $membership->discipline_id);
                                $set('total_free_classes', $membership->discipline_quantity);
                                $set('remaining_free_classes', $membership->discipline_quantity);
                            }
                        }
                    }),

                Forms\Components\Select::make('discipline_id')
                    ->label('Disciplina')
                    ->relationship('discipline', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),

                Forms\Components\TextInput::make('total_free_classes')
                    ->label('Total de Clases Gratis')
                    ->numeric()
                    ->required()
                    ->minValue(1)
                    ->default(1),

                Forms\Components\TextInput::make('remaining_free_classes')
                    ->label('Clases Gratis Restantes')
                    ->numeric()
                    ->required()
                    ->minValue(0),

                Forms\Components\TextInput::make('used_free_classes')
                    ->label('Clases Gratis Usadas')
                    ->numeric()
                    ->required()
                    ->minValue(0)
                    ->default(0),

                Forms\Components\DatePicker::make('activation_date')
                    ->label('Fecha de Activación')
                    ->required()
                    ->default(now()),

                Forms\Components\DatePicker::make('expiry_date')
                    ->label('Fecha de Expiración')
                    ->required()
                    ->after('activation_date'),

                Forms\Components\Select::make('status')
                    ->label('Estado')
                    ->options([
                        'active' => 'Activo',
                        'expired' => 'Expirado',
                        'pending' => 'Pendiente',
                        'suspended' => 'Suspendido',
                        'cancelled' => 'Cancelado',
                    ])
                    ->required()
                    ->default('active'),

                Forms\Components\Select::make('source_package_id')
                    ->label('Paquete Origen')
                    ->relationship('sourcePackage', 'name')
                    ->searchable()
                    ->preload(),

                Forms\Components\Textarea::make('notes')
                    ->label('Notas')
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('membership.name')
            ->columns([
                Tables\Columns\TextColumn::make('membership.name')
                    ->label('Membresía')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('discipline.name')
                    ->label('Disciplina')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('total_free_classes')
                    ->label('Total Clases')
                    ->numeric()
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('used_free_classes')
                    ->label('Usadas')
                    ->numeric()
                    ->sortable()
                    ->alignCenter()
                    ->color('warning'),

                Tables\Columns\TextColumn::make('remaining_free_classes')
                    ->label('Restantes')
                    ->numeric()
                    ->sortable()
                    ->alignCenter()
                    ->color(fn($record) => $record->remaining_free_classes > 0 ? 'success' : 'danger')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('activation_date')
                    ->label('Activación')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('expiry_date')
                    ->label('Expiración')
                    ->date('d/m/Y')
                    ->sortable()
                    ->color(fn($record) => $record->expiry_date < now() ? 'danger' :
                                           ($record->expiry_date < now()->addDays(7) ? 'warning' : 'success')),

                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn($state) => match($state) {
                        'active' => 'success',
                        'expired' => 'danger',
                        'pending' => 'warning',
                        'suspended' => 'gray',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn($state) => match($state) {
                        'active' => 'Activo',
                        'expired' => 'Expirado',
                        'pending' => 'Pendiente',
                        'suspended' => 'Suspendido',
                        'cancelled' => 'Cancelado',
                        default => ucfirst($state),
                    }),

                Tables\Columns\TextColumn::make('sourcePackage.name')
                    ->label('Paquete Origen')
                    ->searchable()
                    ->sortable()
                    ->placeholder('N/A'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'active' => 'Activo',
                        'expired' => 'Expirado',
                        'pending' => 'Pendiente',
                        'suspended' => 'Suspendido',
                        'cancelled' => 'Cancelado',
                    ]),

                Tables\Filters\SelectFilter::make('discipline_id')
                    ->label('Disciplina')
                    ->relationship('discipline', 'name'),

                Tables\Filters\SelectFilter::make('membership_id')
                    ->label('Membresía')
                    ->relationship('membership', 'name'),

                Tables\Filters\Filter::make('expired')
                    ->label('Expiradas')
                    ->query(fn(Builder $query): Builder => $query->where('expiry_date', '<', now())),

                Tables\Filters\Filter::make('expiring_soon')
                    ->label('Por Vencer (7 días)')
                    ->query(fn(Builder $query): Builder => $query->whereBetween('expiry_date', [now(), now()->addDays(7)])),

                Tables\Filters\Filter::make('has_free_classes')
                    ->label('Con Clases Disponibles')
                    ->query(fn(Builder $query): Builder => $query->where('remaining_free_classes', '>', 0)),
            ])
            ->headerActions([
                // Tables\Actions\CreateAction::make()
                //     ->label('Agregar Membresía')
                //     ->modalHeading('Agregar Nueva Membresía')
                //     ->mutateFormDataUsing(function (array $data): array {
                //         $data['user_id'] = $this->ownerRecord->id;
                //         return $data;
                //     }),
            ])
            ->actions([

            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Eliminar Seleccionadas'),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('Sin membresías')
            ->emptyStateDescription('Este usuario no tiene membresías con clases gratis.')
            ->emptyStateIcon('heroicon-o-gift');
    }
}
