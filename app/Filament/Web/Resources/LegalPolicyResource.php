<?php

namespace App\Filament\Web\Resources;

use App\Filament\Web\Resources\LegalPolicyResource\Pages;
use App\Models\LegalPolicy;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LegalPolicyResource extends Resource
{
    protected static ?string $model = LegalPolicy::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Políticas Legales';

    protected static ?string $modelLabel = 'Política Legal';

    protected static ?string $pluralModelLabel = 'Políticas Legales';

    protected static ?string $navigationGroup = 'Gestión Legal';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información de la Política')
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->label('Tipo de Política')
                            ->options(LegalPolicy::getTypes())
                            ->required()
                            ->disabled(fn ($record) => $record !== null), // No permitir cambiar el tipo en edición

                        Forms\Components\TextInput::make('title')
                            ->label('Título')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('subtitle')
                            ->label('Subtítulo')
                            ->maxLength(255)
                            ->columnSpanFull(),



                        Forms\Components\Toggle::make('is_active')
                            ->label('Activo')
                            ->default(true)
                            ->helperText('Solo puede haber una política activa por tipo'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'privacy' => 'success',
                        'terms' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => LegalPolicy::getTypes()[$state] ?? $state)
                    ->sortable(),

                Tables\Columns\TextColumn::make('title')
                    ->label('Título')
                    ->searchable()
                    ->limit(50),

                Tables\Columns\TextColumn::make('subtitle')
                    ->label('Subtítulo')
                    ->limit(50)
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean(),

                Tables\Columns\TextColumn::make('updatedBy.name')
                    ->label('Actualizado por')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Última actualización')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipo')
                    ->options(LegalPolicy::getTypes()),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Estado')
                    ->placeholder('Todos')
                    ->trueLabel('Activos')
                    ->falseLabel('Inactivos'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('type');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLegalPolicies::route('/'),
            'create' => Pages\CreateLegalPolicy::route('/create'),
            'view' => Pages\ViewLegalPolicy::route('/{record}'),
            'edit' => Pages\EditLegalPolicy::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('updatedBy');
    }
}