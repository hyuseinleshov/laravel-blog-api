<?php

use App\Services\StripeService;

test('webhook with invalid signature returns 200 for stripe', function () {
    $stripeMock = \Mockery::mock(StripeService::class);
    $stripeMock->shouldReceive('verifyWebhookSignature')
        ->once()
        ->andReturn(false);

    $this->app->instance(StripeService::class, $stripeMock);

    $response = $this->post('/api/v1/webhooks/stripe', [], [
        'Stripe-Signature' => 'invalid_signature',
    ]);

    $response->assertStatus(200);
});

test('webhook is accessible without authentication', function () {
    $stripeMock = \Mockery::mock(StripeService::class);
    $stripeMock->shouldReceive('verifyWebhookSignature')
        ->once()
        ->andReturn(false);

    $this->app->instance(StripeService::class, $stripeMock);

    $response = $this->post('/api/v1/webhooks/stripe', [], [
        'Stripe-Signature' => 'test_signature',
    ]);

    $response->assertStatus(200);
});
