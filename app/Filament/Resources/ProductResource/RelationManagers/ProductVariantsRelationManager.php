<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use App\Models\Product;
use App\Models\ProductOptionAssignment;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use App\Models\ProductVariantValue;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductVariantsRelationManager extends RelationManager
{
    protected static string $relationship = 'variants';

    protected static ?string $title = 'Variants';

    protected function assignedOptionOptions(): array
    {
        /** @var Product $product */
        $product = $this->getOwnerRecord();

        return $product
            ->optionAssignments()
            ->with('option')
            ->orderBy('sort')
            ->get()
            ->mapWithKeys(fn ($assignment) => [(int) $assignment->option_id => (string) $assignment->option?->name])
            ->all();
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('sku')
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(255),

            Forms\Components\TextInput::make('price')
                ->required()
                ->numeric(),

            Forms\Components\TextInput::make('compare_at_price')
                ->numeric()
                ->nullable(),

            Forms\Components\TextInput::make('stock_on_hand')
                ->required()
                ->numeric()
                ->default(0),

            Forms\Components\TextInput::make('stock_reserved')
                ->numeric()
                ->default(0)
                ->disabled()
                ->dehydrated(false)
                ->helperText('Reserved is managed by the system (checkout/orders).'),

            Forms\Components\Placeholder::make('stock_available')
                ->label('Stock available')
                ->content(fn ($record): string => $record ? (string) $record->stock_available : '-'),

            Forms\Components\TextInput::make('weight_grams')
                ->numeric()
                ->nullable(),

            Forms\Components\Toggle::make('is_active')->default(true),

            Forms\Components\TextInput::make('variant_signature')
                ->disabled()
                ->dehydrated(false),

            Forms\Components\Repeater::make('values')
                ->relationship('values')
                ->schema([
                    Forms\Components\Select::make('option_id')
                        ->label('Option')
                        ->options(fn (): array => $this->assignedOptionOptions())
                        ->required()
                        ->searchable()
                        ->preload()
                        ->live()
                        ->afterStateUpdated(fn (Forms\Set $set) => $set('option_value_id', null)),

                    Forms\Components\Select::make('option_value_id')
                        ->label('Value')
                        ->options(function (Forms\Get $get): array {
                            $optionId = (int) ($get('option_id') ?? 0);
                            if ($optionId <= 0) {
                                return [];
                            }

                            return ProductOptionValue::query()
                                ->where('option_id', $optionId)
                                ->where('is_active', true)
                                ->orderBy('sort')
                                ->pluck('value', 'id')
                                ->mapWithKeys(fn ($value, $id) => [(int) $id => (string) $value])
                                ->all();
                        })
                        ->required()
                        ->searchable()
                        ->preload()
                        ->disabled(fn (Forms\Get $get): bool => ! $get('option_id')),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('sku')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('price')->sortable(),
                Tables\Columns\TextColumn::make('stock_on_hand')->label('On hand')->sortable(),
                Tables\Columns\TextColumn::make('stock_reserved')->label('Reserved')->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('stock_available')
                    ->label('Available')
                    ->getStateUsing(fn ($record) => $record?->stock_available ?? null)
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')->boolean()->sortable(),
                Tables\Columns\TextColumn::make('variant_signature')
                    ->label('Signature')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),

                Tables\Actions\Action::make('generate')
                    ->label('Generate combinations')
                    ->icon('heroicon-o-sparkles')
                    ->form(function () {
                        $product = $this->getOwnerRecord();

                        // options assigned to product
                        $assigned = ProductOptionAssignment::query()
                            ->where('product_id', $product->id)
                            ->with('option.values')
                            ->get();

                        $fields = [
                            Forms\Components\TextInput::make('sku_prefix')
                                ->label('SKU prefix')
                                ->default(strtoupper(Str::slug($product->name)))
                                ->required(),

                            Forms\Components\TextInput::make('default_price')
                                ->numeric()
                                ->minValue(0)
                                ->required()
                                ->label('Default price')
                                ->default(0),

                            Forms\Components\TextInput::make('default_stock')
                                ->numeric()
                                ->minValue(0)
                                ->required()
                                ->label('Stock on hand')
                                ->default(0),
                        ];

                        foreach ($assigned as $a) {
                            $fields[] = Forms\Components\Select::make('opt_' . $a->option_id)
                                ->label($a->option->name)
                                ->multiple()
                                ->required()
                                ->options(
                                    $a->option->values()
                                        ->where('is_active', true)
                                        ->orderBy('sort')
                                        ->pluck('value', 'id')
                                        ->all()
                                );
                        }

                        return $fields;
                    })
                    ->action(function (array $data) {
                        $product = $this->getOwnerRecord();

                        $skuPrefix = $data['sku_prefix'];
                        $price = (float) $data['default_price'];
                        $stock = (int) $data['default_stock'];

                        // collect selected values per option
                        $byOption = [];
                        foreach ($data as $k => $v) {
                            if (str_starts_with($k, 'opt_')) {
                                $optionId = (int) str_replace('opt_', '', $k);
                                $byOption[$optionId] = array_map('intval', (array) $v);
                            }
                        }

                        if (count($byOption) === 0) {
                            Notification::make()->title('No options selected')->danger()->send();
                            return;
                        }

                        $optionIds = array_keys($byOption);

                        // cartesian product
                        $combos = [[]];
                        foreach ($optionIds as $oid) {
                            $new = [];
                            foreach ($combos as $base) {
                                foreach ($byOption[$oid] as $valueId) {
                                    $new[] = $base + [$oid => $valueId];
                                }
                            }
                            $combos = $new;
                        }

                        $created = 0;
                        $skipped = 0;

                        DB::transaction(function () use ($product, $combos, $skuPrefix, $price, $stock, &$created, &$skipped) {
                            foreach ($combos as $map) {
                                $valueIds = array_values($map);
                                sort($valueIds);
                                $signature = implode('-', $valueIds);

                                // skip if exists
                                $exists = ProductVariant::query()
                                    ->where('product_id', $product->id)
                                    ->where('variant_signature', $signature)
                                    ->exists();

                                if ($exists) {
                                    $skipped++;
                                    continue;
                                }

                                $variant = ProductVariant::create([
                                    'product_id' => $product->id,
                                    'sku' => $skuPrefix . '-' . strtoupper(Str::random(6)),
                                    'price' => $price,
                                    'compare_at_price' => null,
                                    'stock_on_hand' => $stock,
                                    'stock_reserved' => 0,
                                    'is_active' => true,
                                    'variant_signature' => $signature, // set now; observer will match anyway
                                ]);

                                foreach ($map as $optionId => $optionValueId) {
                                    ProductVariantValue::create([
                                        'variant_id' => $variant->id,
                                        'option_id' => $optionId,
                                        'option_value_id' => $optionValueId,
                                    ]);
                                }

                                $created++;
                            }
                        });

                        Notification::make()
                            ->title('Generated variants')
                            ->body("Created: {$created}, Skipped (already existed): {$skipped}")
                            ->success()
                            ->send();
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}
