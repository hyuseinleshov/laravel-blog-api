<?php

namespace App\Filament\Resources\Authors\Tables;

use App\Enums\SubscriptionPlan;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AuthorsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->searchable(),
                TextColumn::make('activeSubscription.plan')
                    ->label('Current Plan')
                    ->badge()
                    ->sortable()
                    ->placeholder('No active plan'),
                TextColumn::make('activeSubscription.valid_to')
                    ->label('Plan Valid Until')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('Never'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('plan')
                    ->label('Subscription Plan')
                    ->options(collect(SubscriptionPlan::cases())->mapWithKeys(fn ($case) => [$case->value => ucfirst($case->value)]))
                    ->query(function ($query, $data) {
                        if (filled($data['value'])) {
                            return $query->whereHas('activeSubscription', function ($query) use ($data) {
                                $query->where('plan', $data['value']);
                            });
                        }
                    }),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
