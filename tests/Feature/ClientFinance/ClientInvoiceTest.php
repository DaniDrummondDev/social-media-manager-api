<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\InteractsWithAuth;

uses(InteractsWithAuth::class);

beforeEach(function () {
    $this->setUpAuth();
    $this->user = $this->createUserInDb();
    $this->orgId = $this->createOrgWithOwner($this->user['id'])['org']['id'];
    $this->headers = $this->authHeaders($this->user['id'], $this->orgId, $this->user['email']);

    // Create a client for invoice tests
    $this->clientId = Str::uuid()->toString();
    $now = now()->toDateTimeString();

    DB::table('clients')->insert([
        'id' => $this->clientId,
        'organization_id' => $this->orgId,
        'name' => 'Invoice Test Client',
        'email' => 'invoice-client@example.com',
        'status' => 'active',
        'created_at' => $now,
        'updated_at' => $now,
    ]);
});

it('creates an invoice with 201', function () {
    $response = $this->postJson('/api/v1/invoices', [
        'client_id' => $this->clientId,
        'reference_month' => '2026-03',
        'items' => [
            [
                'description' => 'Social media management',
                'quantity' => 1,
                'unit_price_cents' => 500000,
            ],
            [
                'description' => 'Content creation (10 posts)',
                'quantity' => 10,
                'unit_price_cents' => 15000,
            ],
        ],
        'discount_cents' => 10000,
        'currency' => 'BRL',
        'due_date' => '2026-03-15',
        'notes' => 'March invoice',
    ], $this->headers);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                    'client_id',
                    'reference_month',
                    'items',
                    'subtotal_cents',
                    'discount_cents',
                    'total_cents',
                    'currency',
                    'status',
                    'due_date',
                    'notes',
                    'created_at',
                    'updated_at',
                ],
            ],
        ]);

    expect($response->json('data.type'))->toBe('invoice');
    expect($response->json('data.attributes.status'))->toBe('draft');
    expect($response->json('data.attributes.client_id'))->toBe($this->clientId);
    expect($response->json('data.attributes.reference_month'))->toBe('2026-03');
    expect(count($response->json('data.attributes.items')))->toBe(2);
});

it('lists invoices with 200', function () {
    $now = now()->toDateTimeString();

    $invoiceId = Str::uuid()->toString();
    DB::table('client_invoices')->insert([
        'id' => $invoiceId,
        'client_id' => $this->clientId,
        'organization_id' => $this->orgId,
        'reference_month' => '2026-01',
        'subtotal_cents' => 500000,
        'discount_cents' => 0,
        'total_cents' => 500000,
        'currency' => 'BRL',
        'status' => 'draft',
        'due_date' => '2026-01-15',
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    DB::table('client_invoice_items')->insert([
        'id' => Str::uuid()->toString(),
        'client_invoice_id' => $invoiceId,
        'description' => 'Service fee',
        'quantity' => 1,
        'unit_price_cents' => 500000,
        'total_cents' => 500000,
        'position' => 0,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $response = $this->getJson('/api/v1/invoices', $this->headers);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'type',
                    'attributes' => [
                        'client_id',
                        'reference_month',
                        'total_cents',
                        'status',
                    ],
                ],
            ],
        ]);

    expect(count($response->json('data')))->toBeGreaterThanOrEqual(1);
});

it('shows an invoice with 200', function () {
    $invoiceId = Str::uuid()->toString();
    $now = now()->toDateTimeString();

    DB::table('client_invoices')->insert([
        'id' => $invoiceId,
        'client_id' => $this->clientId,
        'organization_id' => $this->orgId,
        'reference_month' => '2026-02',
        'subtotal_cents' => 300000,
        'discount_cents' => 5000,
        'total_cents' => 295000,
        'currency' => 'BRL',
        'status' => 'draft',
        'due_date' => '2026-02-15',
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    DB::table('client_invoice_items')->insert([
        'id' => Str::uuid()->toString(),
        'client_invoice_id' => $invoiceId,
        'description' => 'Monthly retainer',
        'quantity' => 1,
        'unit_price_cents' => 300000,
        'total_cents' => 300000,
        'position' => 0,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $response = $this->getJson("/api/v1/invoices/{$invoiceId}", $this->headers);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                    'client_id',
                    'reference_month',
                    'items',
                    'subtotal_cents',
                    'discount_cents',
                    'total_cents',
                    'currency',
                    'status',
                    'due_date',
                ],
            ],
        ]);

    expect($response->json('data.id'))->toBe($invoiceId);
    expect($response->json('data.attributes.total_cents'))->toBe(295000);
});

it('sends an invoice with 200', function () {
    $invoiceId = Str::uuid()->toString();
    $now = now()->toDateTimeString();

    DB::table('client_invoices')->insert([
        'id' => $invoiceId,
        'client_id' => $this->clientId,
        'organization_id' => $this->orgId,
        'reference_month' => '2026-02',
        'subtotal_cents' => 500000,
        'discount_cents' => 0,
        'total_cents' => 500000,
        'currency' => 'BRL',
        'status' => 'draft',
        'due_date' => '2026-02-28',
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    DB::table('client_invoice_items')->insert([
        'id' => Str::uuid()->toString(),
        'client_invoice_id' => $invoiceId,
        'description' => 'Service',
        'quantity' => 1,
        'unit_price_cents' => 500000,
        'total_cents' => 500000,
        'position' => 0,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $response = $this->postJson("/api/v1/invoices/{$invoiceId}/send", [], $this->headers);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                    'status',
                    'sent_at',
                ],
            ],
        ]);

    expect($response->json('data.attributes.status'))->toBe('sent');
    expect($response->json('data.attributes.sent_at'))->not->toBeNull();
});

it('marks an invoice as paid with 200', function () {
    $invoiceId = Str::uuid()->toString();
    $now = now()->toDateTimeString();

    DB::table('client_invoices')->insert([
        'id' => $invoiceId,
        'client_id' => $this->clientId,
        'organization_id' => $this->orgId,
        'reference_month' => '2026-02',
        'subtotal_cents' => 400000,
        'discount_cents' => 0,
        'total_cents' => 400000,
        'currency' => 'BRL',
        'status' => 'sent',
        'due_date' => '2026-02-28',
        'sent_at' => $now,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    DB::table('client_invoice_items')->insert([
        'id' => Str::uuid()->toString(),
        'client_invoice_id' => $invoiceId,
        'description' => 'Service',
        'quantity' => 1,
        'unit_price_cents' => 400000,
        'total_cents' => 400000,
        'position' => 0,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $response = $this->postJson("/api/v1/invoices/{$invoiceId}/mark-paid", [
        'payment_method' => 'pix',
        'payment_notes' => 'Paid via Pix transfer',
    ], $this->headers);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                    'status',
                    'paid_at',
                    'payment_method',
                ],
            ],
        ]);

    expect($response->json('data.attributes.status'))->toBe('paid');
    expect($response->json('data.attributes.paid_at'))->not->toBeNull();
    expect($response->json('data.attributes.payment_method'))->toBe('pix');
});

it('cancels an invoice with 200', function () {
    $invoiceId = Str::uuid()->toString();
    $now = now()->toDateTimeString();

    DB::table('client_invoices')->insert([
        'id' => $invoiceId,
        'client_id' => $this->clientId,
        'organization_id' => $this->orgId,
        'reference_month' => '2026-02',
        'subtotal_cents' => 200000,
        'discount_cents' => 0,
        'total_cents' => 200000,
        'currency' => 'BRL',
        'status' => 'draft',
        'due_date' => '2026-02-28',
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    DB::table('client_invoice_items')->insert([
        'id' => Str::uuid()->toString(),
        'client_invoice_id' => $invoiceId,
        'description' => 'Service',
        'quantity' => 1,
        'unit_price_cents' => 200000,
        'total_cents' => 200000,
        'position' => 0,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $response = $this->postJson("/api/v1/invoices/{$invoiceId}/cancel", [], $this->headers);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                    'status',
                ],
            ],
        ]);

    expect($response->json('data.attributes.status'))->toBe('cancelled');
});

it('cannot update a sent invoice', function () {
    $invoiceId = Str::uuid()->toString();
    $now = now()->toDateTimeString();

    DB::table('client_invoices')->insert([
        'id' => $invoiceId,
        'client_id' => $this->clientId,
        'organization_id' => $this->orgId,
        'reference_month' => '2026-02',
        'subtotal_cents' => 500000,
        'discount_cents' => 0,
        'total_cents' => 500000,
        'currency' => 'BRL',
        'status' => 'sent',
        'due_date' => '2026-02-28',
        'sent_at' => $now,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    DB::table('client_invoice_items')->insert([
        'id' => Str::uuid()->toString(),
        'client_invoice_id' => $invoiceId,
        'description' => 'Service',
        'quantity' => 1,
        'unit_price_cents' => 500000,
        'total_cents' => 500000,
        'position' => 0,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $response = $this->patchJson("/api/v1/invoices/{$invoiceId}", [
        'items' => [
            [
                'description' => 'Changed service',
                'quantity' => 2,
                'unit_price_cents' => 250000,
            ],
        ],
        'discount_cents' => 0,
        'due_date' => '2026-03-15',
    ], $this->headers);

    $response->assertStatus(422);

    expect($response->json('errors.0.code'))->toBe('INVOICE_NOT_EDITABLE');
});
