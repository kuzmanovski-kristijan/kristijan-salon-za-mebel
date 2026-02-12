<?php

namespace App\Filament\Resources\CouponResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class CouponRedemptionsRelationManager extends RelationManager
{
    protected static string $relationship = 'redemptions';

    protected static ?string $title = 'Redemptions';

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('redeemed_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('redeemed_at')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('order.number')->label('Order')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('user.email')->label('User')->placeholder('Guest')->toggleable(),
            ]);
    }
}

