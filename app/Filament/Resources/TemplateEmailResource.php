<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TemplateEmailResource\Pages;
use App\Filament\Resources\TemplateEmailResource\RelationManagers;
use App\Models\TemplateEmail;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TemplateEmailResource extends Resource
{
    protected static ?string $model = TemplateEmail::class;

protected static ?string $navigationIcon = 'heroicon-o-envelope';

    protected static ?string $navigationGroup = 'Configuración General';

    protected static ?string $navigationLabel = 'Plantillas de correo';

    protected static ?string $label = 'Plantilla de correo'; // Nombre en singular
    protected static ?string $pluralLabel = 'Plantillas de correo'; // Nombre en plural

    protected static ?int $navigationSort = 26;


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información básica')
                    ->description('Configura los detalles principales de la plantilla de correo.')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre de la plantilla')
                            ->required()
                            ->disabled(fn (string $context): bool => $context === 'edit')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('subject')
                            ->label('Asunto del correo')
                            ->required()
                            ->maxLength(255),
                        // Forms\Components\TextInput::make('title')
                        //     ->label('Título opcional')
                        //     ->maxLength(255),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Contenido del correo')
                    ->description('Define el cuerpo del correo.')
                    ->schema([
                        Forms\Components\RichEditor::make('body')
                            ->label('Cuerpo del correo')
                            ->required()
                            ->columnSpanFull()
                            ->fileAttachmentsDirectory('attachments')
                            ->toolbarButtons([
                                'attachFiles',
                                'blockquote',
                                'bold',
                                'bulletList',
                                'codeBlock',
                                'h2',
                                'h3',
                                'italic',
                                'link',
                                'orderedList',
                                'redo',
                                'strike',
                                'undo',
                            ]),
                    ]),

                Forms\Components\Section::make('Adjuntos y metadatos')
                    ->description('Configura archivos adjuntos y metadatos adicionales.')
                    ->schema([
                        Forms\Components\Repeater::make('attachments')
                            ->label('Archivos adjuntos con orden')
                            ->schema([
                                Forms\Components\FileUpload::make('file')
                                    ->label('Archivo')
                                    ->directory('attachments')
                                    ->openable()
                                    ->downloadable()
                                    ->previewable()
                                    ->required(),
                                Forms\Components\TextInput::make('order')
                                    ->label('Orden')
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(1)
                                    ->required()
                                    ->helperText('Número de orden (1 = primero, 2 = segundo, etc.)'),
                            ])
                            ->columns(2)
                            ->addActionLabel('Agregar archivo')
                            ->reorderable()
                            ->collapsible()
                            ->itemLabel(fn (array $state): string => 'Archivo ' . ($state['order'] ?? 'sin orden'))
                            ->columnSpanFull(),
                        // Forms\Components\KeyValue::make('metadata')
                        //     ->label('Metadatos')
                        //     ->columnSpanFull(),
                        // Forms\Components\Toggle::make('is_active')
                        //     ->label('¿Plantilla activa?')
                        //     ->required()
                        //     ->default(true),
                    ]),
            ]);
    }


    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Correo')
                    ->searchable(),
                Tables\Columns\TextColumn::make('subject')
                    ->label('Asunto')
                    ->searchable(),

                // Tables\Columns\TextColumn::make('created_at')
                //     ->label('Creado')
                //     ->dateTime('d/m/Y H:i')
                //     ->sortable()
                //     ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListTemplateEmails::route('/'),
            'create' => Pages\CreateTemplateEmail::route('/create'),
            'edit' => Pages\EditTemplateEmail::route('/{record}/edit'),
        ];
    }
}
