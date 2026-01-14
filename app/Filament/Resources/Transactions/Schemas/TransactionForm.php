<?php

namespace App\Filament\Resources\Transactions\Schemas;

use App\Enums\SubscriptionPlan;
use App\Enums\TransactionStatus;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TransactionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('author_id')
                    ->relationship('author', 'name')
                    ->required()
                    ->searchable(),
                Select::make('subscription_id')
                    ->relationship('subscription', 'id')
                    ->nullable()
                    ->searchable(),
                TextInput::make('stripe_payment_id')
                    ->maxLength(255)
                    ->required(),
                TextInput::make('amount')
                    ->required()
                    ->numeric()
                    ->minValue(0),
                TextInput::make('currency')
                    ->required()
                    ->maxLength(3)
                    ->default('usd'),
                Select::make('plan')
                    ->options(SubscriptionPlan::class)
                    ->required(),
                Select::make('status')
                    ->options(TransactionStatus::class)
                    ->required(),
                KeyValue::make('metadata')
                    ->nullable()
                    ->columnSpanFull(),
            ]);
    }
}
