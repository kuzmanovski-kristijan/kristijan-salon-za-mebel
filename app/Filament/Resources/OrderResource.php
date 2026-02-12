<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Concerns\StaffResource;
use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers\OrderHistoryRelationManager;
use App\Filament\Resources\OrderResource\RelationManagers\OrderItemsRelationManager;
use App\Models\Order;
use App\Services\InventoryService;
use App\Services\OrderStatusService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class OrderResource extends Resource
{
    use StaffResource;

    protected static ?string $model = Order::class;
    protected static ?string $navigationGroup = 'Sales';
    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';
    protected static ?int $navigationSort = 10;

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

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('number')->disabled()->dehydrated(false),
            Forms\Components\Select::make('status')
                ->options([
                    'new' => 'New',
                    'confirmed' => 'Confirmed',
                    'packed' => 'Packed',
                    'shipped' => 'Shipped',
                    'delivered' => 'Delivered',
                    'canceled' => 'Canceled',
                ])
                ->disabled()
                ->dehydrated(false),

            Forms\Components\Select::make('payment_status')
                ->options([
                    'unpaid' => 'Unpaid',
                    'paid' => 'Paid',
                    'refunded' => 'Refunded',
                ])
                ->disabled()
                ->dehydrated(false),

            Forms\Components\Select::make('shipping_status')
                ->options([
                    'unshipped' => 'Unshipped',
                    'shipped' => 'Shipped',
                    'delivered' => 'Delivered',
                    'canceled' => 'Canceled',
                ])
                ->disabled()
                ->dehydrated(false),

            Forms\Components\TextInput::make('grand_total')->disabled()->dehydrated(false),
            Forms\Components\TextInput::make('currency')->disabled()->dehydrated(false),
            Forms\Components\DateTimePicker::make('placed_at')->seconds(false)->disabled()->dehydrated(false),

            Forms\Components\Textarea::make('notes')->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('placed_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('number')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('user.email')
                    ->label('Customer')
                    ->placeholder('Guest')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'new' => 'gray',
                        'confirmed' => 'info',
                        'packed' => 'warning',
                        'shipped' => 'warning',
                        'delivered' => 'success',
                        'canceled' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('payment_status')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('shipping_status')
                    ->badge()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('grand_total')->sortable(),
                Tables\Columns\TextColumn::make('currency')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('placed_at')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    'new' => 'New',
                    'confirmed' => 'Confirmed',
                    'packed' => 'Packed',
                    'shipped' => 'Shipped',
                    'delivered' => 'Delivered',
                    'canceled' => 'Canceled',
                ]),
                Tables\Filters\SelectFilter::make('payment_status')->options([
                    'unpaid' => 'Unpaid',
                    'paid' => 'Paid',
                    'refunded' => 'Refunded',
                ]),
                Tables\Filters\SelectFilter::make('shipping_status')->options([
                    'unshipped' => 'Unshipped',
                    'shipped' => 'Shipped',
                    'delivered' => 'Delivered',
                    'canceled' => 'Canceled',
                ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),

                Tables\Actions\Action::make('confirm')
                    ->label('Confirm')
                    ->icon('heroicon-o-check')
                    ->visible(fn (Order $record): bool => $record->status === 'new')
                    ->requiresConfirmation()
                    ->action(function (Order $record): void {
                        app(OrderStatusService::class)->transition($record, 'confirmed');
                        Notification::make()->title('Order confirmed')->success()->send();
                    }),

                Tables\Actions\Action::make('pack')
                    ->label('Pack')
                    ->icon('heroicon-o-archive-box')
                    ->visible(fn (Order $record): bool => $record->status === 'confirmed')
                    ->requiresConfirmation()
                    ->action(function (Order $record): void {
                        app(OrderStatusService::class)->transition($record, 'packed');
                        Notification::make()->title('Order packed')->success()->send();
                    }),

                Tables\Actions\Action::make('ship')
                    ->label('Ship')
                    ->icon('heroicon-o-truck')
                    ->visible(fn (Order $record): bool => $record->status === 'packed')
                    ->requiresConfirmation()
                    ->action(function (Order $record): void {
                        app(OrderStatusService::class)->transition($record, 'shipped');
                        Notification::make()->title('Order shipped')->success()->send();
                    }),

                Tables\Actions\Action::make('deliver')
                    ->label('Deliver')
                    ->icon('heroicon-o-check-badge')
                    ->visible(fn (Order $record): bool => $record->status === 'shipped')
                    ->requiresConfirmation()
                    ->action(function (Order $record): void {
                        DB::transaction(function () use ($record): void {
                            app(OrderStatusService::class)->transition($record, 'delivered');

                            if (class_exists(InventoryService::class)) {
                                $record->loadMissing('items');
                                $inventory = app(InventoryService::class);

                                foreach ($record->items as $item) {
                                    if ($item->variant_id) {
                                        $inventory->commitSale((int) $item->variant_id, (int) $item->qty);
                                    }
                                }
                            }
                        });

                        Notification::make()->title('Order delivered')->success()->send();
                    }),

                Tables\Actions\Action::make('cancel')
                    ->label('Cancel')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Order $record): bool => in_array($record->status, ['new', 'confirmed', 'packed'], true))
                    ->form([
                        Forms\Components\Textarea::make('reason')->label('Reason')->maxLength(255),
                    ])
                    ->requiresConfirmation()
                    ->action(function (Order $record, array $data): void {
                        DB::transaction(function () use ($record, $data): void {
                            app(OrderStatusService::class)->transition($record, 'canceled', $data['reason'] ?? null);

                            if (class_exists(InventoryService::class)) {
                                $record->loadMissing('items');
                                $inventory = app(InventoryService::class);

                                foreach ($record->items as $item) {
                                    if ($item->variant_id) {
                                        $inventory->release((int) $item->variant_id, (int) $item->qty);
                                    }
                                }
                            }
                        });

                        Notification::make()->title('Order canceled')->success()->send();
                    }),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            OrderItemsRelationManager::class,
            OrderHistoryRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}
