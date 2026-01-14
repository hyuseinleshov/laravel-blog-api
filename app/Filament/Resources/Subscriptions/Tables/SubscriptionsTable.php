<?php

namespace App\Filament\Resources\Subscriptions\Tables;

use App\Enums\SubscriptionPlan;
use App\Enums\SubscriptionStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SubscriptionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('author.name')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('plan')
                    ->badge()
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->searchable(),
                TextColumn::make('valid_from')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('valid_to')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('Never'),
                TextColumn::make('stripe_payment_intent_id')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
                    ->options([
                        SubscriptionPlan::BASIC->value => 'Basic',
                        SubscriptionPlan::MEDIUM->value => 'Medium',
                        SubscriptionPlan::PREMIUM->value => 'Premium',
                    ]),
                SelectFilter::make('status')
                    ->options([
                        SubscriptionStatus::PENDING->value => 'Pending',
                        SubscriptionStatus::ACTIVE->value => 'Active',
                        SubscriptionStatus::CANCELLED->value => 'Cancelled',
                        SubscriptionStatus::EXPIRED->value => 'Expired',
                    ]),
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
