<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AppointmentResource\Pages;
use App\Filament\Resources\Concerns\StaffResource;
use App\Models\Appointment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AppointmentResource extends Resource
{
    use StaffResource;

    protected static ?string $model = Appointment::class;
    protected static ?string $navigationGroup = 'Scheduling';
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('store_id')
                ->relationship('store', 'name')
                ->required()
                ->searchable()
                ->preload(),

            Forms\Components\Select::make('status')
                ->options([
                    'new' => 'New',
                    'confirmed' => 'Confirmed',
                    'cancelled' => 'Cancelled',
                    'done' => 'Done',
                ])
                ->required()
                ->default('new'),

            Forms\Components\TextInput::make('customer_name')->required()->maxLength(255),
            Forms\Components\TextInput::make('customer_phone')->nullable()->maxLength(255),
            Forms\Components\TextInput::make('customer_email')->email()->nullable()->maxLength(255),

            Forms\Components\DateTimePicker::make('starts_at')->seconds(false)->required(),
            Forms\Components\DateTimePicker::make('ends_at')->seconds(false)->nullable(),

            Forms\Components\Textarea::make('note')->columnSpanFull()->rows(4)->nullable(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('starts_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('store.name')->label('Store')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('starts_at')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('status')->badge()->sortable(),
                Tables\Columns\TextColumn::make('customer_name')->label('Customer')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('customer_phone')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('customer_email')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('note')->wrap()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    'new' => 'New',
                    'confirmed' => 'Confirmed',
                    'cancelled' => 'Cancelled',
                    'done' => 'Done',
                ]),
                Tables\Filters\SelectFilter::make('store_id')
                    ->relationship('store', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),

                Tables\Actions\Action::make('confirm')
                    ->label('Confirm')
                    ->icon('heroicon-o-check')
                    ->visible(fn (Appointment $record): bool => $record->status === 'new')
                    ->requiresConfirmation()
                    ->action(function (Appointment $record): void {
                        $record->update(['status' => 'confirmed']);
                        Notification::make()->title('Appointment confirmed')->success()->send();
                    }),

                Tables\Actions\Action::make('done')
                    ->label('Done')
                    ->icon('heroicon-o-check-badge')
                    ->visible(fn (Appointment $record): bool => $record->status === 'confirmed')
                    ->requiresConfirmation()
                    ->action(function (Appointment $record): void {
                        $record->update(['status' => 'done']);
                        Notification::make()->title('Appointment marked as done')->success()->send();
                    }),

                Tables\Actions\Action::make('cancel')
                    ->label('Cancel')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Appointment $record): bool => in_array($record->status, ['new', 'confirmed'], true))
                    ->requiresConfirmation()
                    ->action(function (Appointment $record): void {
                        $record->update(['status' => 'cancelled']);
                        Notification::make()->title('Appointment cancelled')->success()->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAppointments::route('/'),
            'create' => Pages\CreateAppointment::route('/create'),
            'edit' => Pages\EditAppointment::route('/{record}/edit'),
        ];
    }
}

