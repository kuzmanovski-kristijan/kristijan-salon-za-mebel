<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ProductImagesRelationManager extends RelationManager
{
    protected static string $relationship = 'images';

    protected static ?string $title = 'Images';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\FileUpload::make('path')
                ->label('Image')
                ->disk('public')
                ->directory('products')
                ->image()
                ->imageEditor()
                ->required(),

            Forms\Components\TextInput::make('alt')
                ->maxLength(255)
                ->nullable(),

            Forms\Components\TextInput::make('sort')
                ->numeric()
                ->default(0)
                ->required(),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->reorderable('sort')
            ->columns([
                Tables\Columns\ImageColumn::make('path')
                    ->disk('public')
                    ->label(''),

                Tables\Columns\TextColumn::make('alt')
                    ->wrap(),

                Tables\Columns\TextColumn::make('sort')->sortable(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}
