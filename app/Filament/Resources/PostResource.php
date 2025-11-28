<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PostResource\Pages;
use App\Models\Post;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PostResource extends Resource
{
    protected static ?string $model = Post::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';


    protected static ?string $navigationGroup = 'News';

    protected static ?string $navigationLabel = 'Artículos';

    protected static ?string $label = 'Artículo'; // Nombre en singular
    protected static ?string $pluralLabel = 'Artículos'; // Nombre en plural

    protected static ?int $navigationSort = 18;



    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Toggle::make('is_featured')
                    ->label('Destacado')
                    ->default(false),
                Section::make('Información del Artículo')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Título')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\FileUpload::make('image_path')
                            ->label('Imagen principal')
                            ->image()
                            ->directory('posts')
                            ->disk('public') // Usa el filesystem configurado como 'public'
                            ->preserveFilenames()
                            ->columnSpanFull()
                            ->required(fn(callable $get) => $get('status') === 'published')
                            ->live(), // Importante: notifica cambios en tiempo real
                        Forms\Components\Select::make('status')
                            ->options([
                                'draft' => 'Borrador',
                                'published' => 'Publicado',
                                'Dismissed' => 'Desestimado'
                            ])
                            ->default('draft')
                            ->live() // Importante: notifica cambios en tiempo real
                            ->required()

                    ]),

                Section::make('Contenido')
                    ->schema([
                        Forms\Components\RichEditor::make('content')
                            ->label('Contenido')
                            ->required(fn(callable $get) => $get('status') === 'published')
                            ->columnSpanFull()
                            ->live() // Importante: notifica cambios en tiempo real
                            ->toolbarButtons([
                                'bold',
                                'italic',
                                'underline',
                                'bulletList',
                                'orderedList',
                                'link',
                                'blockquote',
                            ]),
                    ]),

                Section::make('Relaciones')
                    ->schema([
                        Forms\Components\Select::make('category_id')
                            ->label('Categoría')
                            ->required(fn(callable $get) => $get('status') === 'published')
                            ->live() // Importante: notifica cambios en tiempo real
                            ->relationship('category', 'name'),


                        Forms\Components\Select::make('user_id')
                            ->label('Usuario')
                            ->required(fn() => auth()->user()?->hasRole('super_admin') || auth()->user()?->hasRole('Administrador'))
                            ->visible(fn() => auth()->user()?->hasRole('super_admin') || auth()->user()?->hasRole('Administrador'))
                            ->relationship('user', 'name', function ($query) {
                                // Excluir usuarios con rol de cliente
                                return $query->whereDoesntHave('roles', function ($q) {
                                    $q->where('name', 'Cliente');
                                });
                            })
                            ->default(fn() => auth()->id()),
                        // Forms\Components\Select::make('tags')
                        //     ->label('Etiquetas')
                        //     ->multiple()
                        //     ->relationship('tags', 'name')
                        //     ->preload(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Título')
                    ->searchable(),
                // Tables\Columns\ImageColumn::make('image_path')
                //     ->label('Imagen'),
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Categoría')
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Usuario')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'draft' => 'warning',
                        'published' => 'success',
                        'Dismissed' => 'gray',
                        default => 'gray',
                    })->formatStateUsing(fn(string $state): string => match ($state) {
                        'draft' => 'Borrador',
                        'published' => 'Publicado',
                        'Dismissed' => 'Desestimado',
                        default => $state,
                    }),



                // Tables\Columns\TextColumn::make('tags.name')
                //     ->label('Etiquetas')
                //     ->badge()
                //     ->separator(', '),
                // Tables\Columns\TextColumn::make('created_at')
                //     ->label('Creado el')
                //     ->dateTime()
                //     ->sortable()
                //     ->toggleable(isToggledHiddenByDefault: true),
                // Tables\Columns\TextColumn::make('updated_at')
                //     ->label('Actualizado el')
                //     ->dateTime()
                //     ->sortable()
                //     ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                // Agrega filtros si es necesario
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
            'index' => Pages\ListPosts::route('/'),
            'create' => Pages\CreatePost::route('/create'),
            'edit' => Pages\EditPost::route('/{record}/edit'),
        ];
    }
}
