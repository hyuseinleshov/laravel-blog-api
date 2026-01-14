<?php

namespace App\Filament\Resources\Subscriptions\RelationManagers;

use App\Enums\TransactionStatus;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TransactionsRelationManager extends RelationManager
{
    protected static string $relationship = 'transactions';

    protected static ?string $recordTitleAttribute = 'stripe_payment_id';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('stripe_payment_id')
                    ->searchable()
                    ->label('Payment ID'),
                TextColumn::make('amount')
                    ->money('currency')
                    ->sortable(),
                TextColumn::make('plan')
                    ->badge()
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Date'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        TransactionStatus::PENDING->value => 'Pending',
                        TransactionStatus::COMPLETED->value => 'Completed',
                        TransactionStatus::FAILED->value => 'Failed',
                        TransactionStatus::REFUNDED->value => 'Refunded',
                    ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                //
            ]);
    }
}
