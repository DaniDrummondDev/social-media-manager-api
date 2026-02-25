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

    // Create a client with invoices and costs for report data
    $this->clientId = Str::uuid()->toString();
    $now = now()->toDateTimeString();

    DB::table('clients')->insert([
        'id' => $this->clientId,
        'organization_id' => $this->orgId,
        'name' => 'Report Test Client',
        'email' => 'report@example.com',
        'status' => 'active',
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    // Create a paid invoice (revenue)
    $invoiceId = Str::uuid()->toString();
    DB::table('client_invoices')->insert([
        'id' => $invoiceId,
        'client_id' => $this->clientId,
        'organization_id' => $this->orgId,
        'reference_month' => '2026-02',
        'subtotal_cents' => 500000,
        'discount_cents' => 0,
        'total_cents' => 500000,
        'currency' => 'BRL',
        'status' => 'paid',
        'due_date' => '2026-02-15',
        'paid_at' => $now,
        'payment_method' => 'pix',
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    DB::table('client_invoice_items')->insert([
        'id' => Str::uuid()->toString(),
        'client_invoice_id' => $invoiceId,
        'description' => 'Monthly retainer',
        'quantity' => 1,
        'unit_price_cents' => 500000,
        'total_cents' => 500000,
        'position' => 0,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    // Create a cost allocation
    DB::table('cost_allocations')->insert([
        'id' => Str::uuid()->toString(),
        'client_id' => $this->clientId,
        'organization_id' => $this->orgId,
        'resource_type' => 'campaign',
        'description' => 'Ad spend',
        'cost_cents' => 100000,
        'currency' => 'BRL',
        'allocated_at' => $now,
        'created_at' => $now,
        'updated_at' => $now,
    ]);
});

it('returns dashboard with 200', function () {
    $response = $this->getJson('/api/v1/client-reports/dashboard', $this->headers);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'total_revenue_cents',
                'total_cost_cents',
                'profit_cents',
                'margin_percent',
                'active_clients',
                'active_contracts',
                'overdue_invoices',
                'draft_invoices',
            ],
        ]);

    expect($response->json('data.active_clients'))->toBeGreaterThanOrEqual(1);
});

it('returns profitability report with 200', function () {
    $response = $this->getJson('/api/v1/client-reports/profitability', $this->headers);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'revenue_cents',
                'cost_cents',
                'profit_cents',
                'margin_percent',
            ],
        ]);
});

it('returns 401 without auth', function () {
    $response = $this->getJson('/api/v1/client-reports/dashboard');

    $response->assertStatus(401);
});
