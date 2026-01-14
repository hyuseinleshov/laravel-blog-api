<?php

namespace App\Filament\Resources\Subscriptions\Schemas;

use App\Enums\SubscriptionPlan;
use App\Enums\SubscriptionStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SubscriptionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('author_id')
                    ->relationship('author', 'name')
                    ->required()
                    ->searchable(),
                Select::make('plan')
                    ->options(SubscriptionPlan::class)
                    ->required(),
                Select::make('status')
                    ->options(SubscriptionStatus::class)
                    ->required(),
                DateTimePicker::make('valid_from')
                    ->required(),
                DateTimePicker::make('valid_to')
                    ->nullable(),
                TextInput::make('stripe_payment_intent_id')
                    ->maxLength(255)
                    ->nullable(),
            ]);
    }
}
