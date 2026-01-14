<?php

namespace App\Filament\Resources\Transactions\Tables;

use App\Enums\SubscriptionPlan;
use App\Enums\TransactionStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('author.name')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('subscription.id')
                    ->sortable()
                    ->searchable()
                    ->placeholder('N/A'),
                TextColumn::make('stripe_payment_id')
                    ->searchable(),
                TextColumn::make('amount')
                    ->money('currency')
                    ->sortable(),
                TextColumn::make('currency')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('plan')
                    ->badge()
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->searchable(),
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
                SelectFilter::make('author')
                    ->relationship('author', 'name')
                    ->searchable(),
                SelectFilter::make('status')
                    ->options([
                        TransactionStatus::PENDING->value => 'Pending',
                        TransactionStatus::COMPLETED->value => 'Completed',
                        TransactionStatus::FAILED->value => 'Failed',
                        TransactionStatus::REFUNDED->value => 'Refunded',
                    ]),
            ])
            ->recordActions([
                //
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
