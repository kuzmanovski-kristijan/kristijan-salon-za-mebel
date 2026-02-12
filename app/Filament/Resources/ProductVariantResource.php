<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Concerns\StaffResource;
use App\Filament\Resources\ProductVariantResource\Pages;
use App\Models\ProductVariant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ProductVariantResource extends Resource
{
    use StaffResource;

    protected static ?string $model = ProductVariant::class;
    protected static ?string $navigationGroup = 'Inventory';
    protected static ?string $navigationIcon = 'heroicon-o-circle-stack';
    protected static ?int $navigationSort = 10;

    protected static ?string $navigationLabel = 'Stock';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function canForceDelete(Model $record): bool
    {
        return false;
    }

    public static function canForceDeleteAny(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Variant')
                ->schema([
                    Forms\Components\Placeholder::make('product')
                        ->label('Product')
                        ->content(fn (?ProductVariant $record): string => $record?->product?->name ?? '-'),

                    Forms\Components\TextInput::make('sku')
                        ->disabled()
                        ->dehydrated(false),

                    Forms\Components\Placeholder::make('options')
                        ->label('Options')
                        ->content(function (?ProductVariant $record): string {
                            if (! $record) {
                                return '-';
                            }

                            $record->loadMissing('values.option', 'values.optionValue');

                            $pairs = $record->values
                                ->sortBy(fn ($v) => (int) ($v->option?->sort ?? 0))
                                ->map(fn ($v) => trim((string) ($v->option?->name ?? '').': '.(string) ($v->optionValue?->value ?? '')))
                                ->filter()
                                ->values()
                                ->all();

                            return $pairs ? implode(', ', $pairs) : '-';
                        })
                        ->columnSpanFull(),
                ])
                ->columns(2),

            Forms\Components\Section::make('Stock')
                ->schema([
                    Forms\Components\TextInput::make('stock_on_hand')
                        ->required()
                        ->numeric()
                        ->minValue(0),

                    Forms\Components\TextInput::make('stock_reserved')
                        ->disabled()
                        ->dehydrated(false)
                        ->helperText('Reserved is managed by the system (checkout/orders).'),

                    Forms\Components\Placeholder::make('stock_available')
                        ->label('Stock available')
                        ->content(fn (?ProductVariant $record): string => $record ? (string) $record->stock_available : '-'),
                ])
                ->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['product']))
            ->defaultSort('updated_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('sku')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('product.name')->label('Product')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('stock_on_hand')->label('On hand')->sortable(),
                Tables\Columns\TextColumn::make('stock_reserved')->label('Reserved')->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('stock_available')
                    ->label('Available')
                    ->getStateUsing(fn ($record) => $record?->stock_available ?? null)
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')->boolean()->sortable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')->since()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')->label('Active'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Edit stock'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProductVariants::route('/'),
            'edit' => Pages\EditProductVariant::route('/{record}/edit'),
        ];
    }
}

