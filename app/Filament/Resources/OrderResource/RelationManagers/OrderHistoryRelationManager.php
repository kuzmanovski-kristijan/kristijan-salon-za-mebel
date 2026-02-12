<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class OrderHistoryRelationManager extends RelationManager
{
    protected static string $relationship = 'history';

    protected static ?string $title = 'Status history';

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('changed_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('changed_at')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('from_status')->label('From')->toggleable(),
                Tables\Columns\TextColumn::make('to_status')->label('To')->badge()->sortable(),
                Tables\Columns\TextColumn::make('byUser.email')->label('By')->toggleable(),
                Tables\Columns\TextColumn::make('reason')->wrap()->toggleable(isToggledHiddenByDefault: true),
            ]);
    }
}

