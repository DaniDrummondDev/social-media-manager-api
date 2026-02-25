<?php

declare(strict_types=1);

it('returns 200 for valid webhook payload', function () {
    $payload = [
        'id' => 'evt_test_'.uniqid(),
        'type' => 'customer.subscription.created',
        'data' => [
            'object' => [
                'id' => 'sub_test_123',
                'status' => 'active',
                'customer' => 'cus_test_123',
                'current_period_start' => time(),
                'current_period_end' => time() + 2592000,
                'items' => [
                    'data' => [
                        ['price' => ['recurring' => ['interval' => 'month']]],
                    ],
                ],
            ],
        ],
    ];

    $response = $this->postJson('/api/v1/webhooks/stripe', $payload, [
        'Stripe-Signature' => 'test_signature',
    ]);

    // The StubPaymentGateway validates all signatures as true and decodes JSON payload.
    // Event references sub_test_123 which does not exist in DB, so handler silently returns.
    $response->assertStatus(200);
    expect($response->json('received'))->toBeTrue();
});

it('handles idempotency for duplicate event ID', function () {
    $eventId = 'evt_idempotent_'.uniqid();

    $payload = [
        'id' => $eventId,
        'type' => 'customer.subscription.updated',
        'data' => [
            'object' => [
                'id' => 'sub_test_456',
                'status' => 'active',
            ],
        ],
    ];

    // First request
    $first = $this->postJson('/api/v1/webhooks/stripe', $payload, [
        'Stripe-Signature' => 'test_signature',
    ]);
    $first->assertStatus(200);

    // Second request with same event ID — should still return 200 (idempotent)
    $second = $this->postJson('/api/v1/webhooks/stripe', $payload, [
        'Stripe-Signature' => 'test_signature',
    ]);
    $second->assertStatus(200);
    expect($second->json('received'))->toBeTrue();
});

it('does not require auth for webhook endpoint', function () {
    $payload = [
        'id' => 'evt_noauth_'.uniqid(),
        'type' => 'invoice.paid',
        'data' => [
            'object' => [
                'id' => 'in_test_789',
                'subscription' => 'sub_none',
                'amount_paid' => 4900,
                'currency' => 'brl',
                'hosted_invoice_url' => 'https://stripe.com/invoice/123',
                'period_start' => time(),
                'period_end' => time() + 2592000,
            ],
        ],
    ];

    // No Authorization header — should still work
    $response = $this->postJson('/api/v1/webhooks/stripe', $payload, [
        'Stripe-Signature' => 'test_signature',
    ]);

    $response->assertStatus(200);
});
