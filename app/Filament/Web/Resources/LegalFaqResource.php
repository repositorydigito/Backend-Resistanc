<?php

namespace App\Filament\Web\Resources;

use App\Filament\Web\Resources\LegalFaqResource\Pages;
use App\Models\LegalFaq;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LegalFaqResource extends Resource
{
    protected static ?string $model = LegalFaq::class;

    protected static ?string $navigationIcon = 'heroicon-o-question-mark-circle';

    protected static ?string $navigationLabel = 'FAQs Legales';

    protected static ?string $modelLabel = 'FAQ Legal';

    protected static ?string $pluralModelLabel = 'FAQs Legales';

    protected static ?string $navigationGroup = 'Gestión Legal';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información de la FAQ Legal')
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->label('Tipo de Política')
                            ->options(LegalFaq::getTypes())
                            ->required()
                            ->reactive(),

                        Forms\Components\TextInput::make('order')
                            ->label('Orden')
                            ->numeric()
                            ->default(0)
                            ->helperText('Orden de aparición en la página'),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Activo')
                            ->default(true),

                        Forms\Components\TextInput::make('question')
                            ->label('Pregunta')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\RichEditor::make('answer')
                            ->label('Respuesta')
                            ->required()
                            ->toolbarButtons([
                                'bold',
                                'italic',
                                'underline',
                                'strike',
                                'link',
                                'bulletList',
                                'orderedList',
                                'h2',
                                'h3',
                                'paragraph',
                                'undo',
                                'redo',
                            ])
                            ->columnSpanFull(),
                    ])
                    ->columns(3),
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
                    ->formatStateUsing(fn (string $state): string => LegalFaq::getTypes()[$state] ?? $state)
                    ->sortable(),

                Tables\Columns\TextColumn::make('question')
                    ->label('Pregunta')
                    ->searchable()
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 50) {
                            return null;
                        }
                        return $state;
                    }),

                Tables\Columns\TextColumn::make('answer')
                    ->label('Respuesta')
                    ->limit(100)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 100) {
                            return null;
                        }
                        return $state;
                    }),

                Tables\Columns\TextColumn::make('order')
                    ->label('Orden')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipo')
                    ->options(LegalFaq::getTypes()),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Estado')
                    ->placeholder('Todos')
                    ->trueLabel('Activos')
                    ->falseLabel('Inactivos'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('type')
            ->defaultSort('order')
            ->reorderable('order');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLegalFaqs::route('/'),
            'create' => Pages\CreateLegalFaq::route('/create'),
            'edit' => Pages\EditLegalFaq::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->orderBy('type')->orderBy('order');
    }
}