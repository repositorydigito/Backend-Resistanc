<?php

namespace App\Filament\Web\Resources;

use App\Filament\Web\Resources\HomePageContentResource\Pages;
use App\Models\HomePageContent;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class HomePageContentResource extends Resource
{
    protected static ?string $model = HomePageContent::class;

    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static ?string $navigationLabel = 'Contenido de Inicio';

    protected static ?string $modelLabel = 'Contenido de Página de Inicio';

    protected static ?string $pluralModelLabel = 'Contenido de Página de Inicio';

    protected static ?string $navigationGroup = 'Gestión de Contenido';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información Básica')
                    ->schema([
                        Forms\Components\Select::make('section')
                            ->label('Sección')
                            ->options([
                                'hero' => 'Hero (Banner Principal)',
                                'disciplines' => 'Disciplinas',
                                'packages' => 'Paquetes',
                                'services' => 'Servicios',
                                'download' => 'Descarga de App',
                                'location' => 'Ubicación',
                                'faq' => 'Preguntas Frecuentes',
                            ])
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(fn (callable $set) => $set('key', null)),

                        Forms\Components\TextInput::make('key')
                            ->label('Clave')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Identificador único para este contenido dentro de la sección'),

                        Forms\Components\Select::make('type')
                            ->label('Tipo de Contenido')
                            ->options([
                                'text' => 'Texto',
                                'textarea' => 'Texto Largo',
                                'image' => 'Imagen',
                                'url' => 'URL/Enlace',
                                'email' => 'Email',
                                'phone' => 'Teléfono',
                            ])
                            ->required()
                            ->reactive(),

                        Forms\Components\TextInput::make('order')
                            ->label('Orden')
                            ->numeric()
                            ->default(0)
                            ->helperText('Orden de aparición en la sección'),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Activo')
                            ->default(true),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Contenido')
                    ->schema([


                        Forms\Components\Textarea::make('value')
                            ->label('Valor')
                            ->required()
                            ->rows(function (Forms\Get $get) {
                                return $get('type') === 'textarea' ? 6 : 2;
                            })
                            ->helperText(function (Forms\Get $get) {
                                $type = $get('type');
                                if ($type === 'textarea') {
                                    return 'Contenido largo. Puede usar HTML: <strong>, <em>, <p>, <br>, etc.';
                                }
                                return 'Contenido corto. Para ' . $type . ', escriba el texto directamente.';
                            })
                            ->visible(fn (Forms\Get $get): bool => in_array($get('type'), ['text', 'textarea', 'url', 'email', 'phone'])),

                        Forms\Components\FileUpload::make('value')
                            ->label('Imagen')
                            ->image()
                            ->directory('home-content')
                            ->visibility('public')
                            ->imageEditor()
                            ->imageEditorAspectRatios([
                                null,
                                '16:9',
                                '4:3',
                                '1:1',
                            ])
                            ->visible(fn (Forms\Get $get): bool => $get('type') === 'image'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('section')
                    ->label('Sección')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'hero' => 'success',
                        'disciplines' => 'info',
                        'packages' => 'warning',
                        'services' => 'primary',
                        'download' => 'secondary',
                        'location' => 'danger',
                        'faq' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'hero' => 'Hero',
                        'disciplines' => 'Disciplinas',
                        'packages' => 'Paquetes',
                        'services' => 'Servicios',
                        'download' => 'Descarga',
                        'location' => 'Ubicación',
                        'faq' => 'FAQ',
                        default => $state,
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('key')
                    ->label('Clave')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'text' => 'Texto',
                        'textarea' => 'Texto Largo',
                        'image' => 'Imagen',
                        'url' => 'URL',
                        'email' => 'Email',
                        'phone' => 'Teléfono',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('value')
                    ->label('Contenido')
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 50) {
                            return null;
                        }
                        return $state;
                    })
                    ->formatStateUsing(function ($state, $record) {
                        if ($record->type === 'image') {
                            return '🖼️ ' . basename($state);
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
                Tables\Filters\SelectFilter::make('section')
                    ->label('Sección')
                    ->options([
                        'hero' => 'Hero',
                        'disciplines' => 'Disciplinas',
                        'packages' => 'Paquetes',
                        'services' => 'Servicios',
                        'download' => 'Descarga',
                        'location' => 'Ubicación',
                        'faq' => 'FAQ',
                    ]),

                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipo')
                    ->options([
                        'text' => 'Texto',
                        'textarea' => 'Texto Largo',
                        'image' => 'Imagen',
                        'url' => 'URL',
                        'email' => 'Email',
                        'phone' => 'Teléfono',
                    ]),

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
            ->defaultSort('section')
            ->defaultSort('order');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHomePageContents::route('/'),
            'create' => Pages\CreateHomePageContent::route('/create'),
            'edit' => Pages\EditHomePageContent::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->orderBy('section')->orderBy('order');
    }
}