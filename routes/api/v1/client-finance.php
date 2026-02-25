<?php

declare(strict_types=1);

use App\Infrastructure\ClientFinance\Controllers\ClientController;
use App\Infrastructure\ClientFinance\Controllers\ClientContractController;
use App\Infrastructure\ClientFinance\Controllers\ClientInvoiceController;
use App\Infrastructure\ClientFinance\Controllers\CostAllocationController;
use App\Infrastructure\ClientFinance\Controllers\FinancialReportController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth.jwt'])->group(function () {
    // Clients — read (all roles)
    Route::get('clients', [ClientController::class, 'index']);
    Route::get('clients/{id}', [ClientController::class, 'show']);

    // Clients — write (owner, admin)
    Route::middleware('role:owner,admin')->group(function () {
        Route::post('clients', [ClientController::class, 'store']);
        Route::patch('clients/{id}', [ClientController::class, 'update']);
        Route::post('clients/{id}/archive', [ClientController::class, 'archive']);
    });

    // Contracts — read (all roles)
    Route::get('clients/{clientId}/contracts', [ClientContractController::class, 'index']);

    // Contracts — write (owner, admin)
    Route::middleware('role:owner,admin')->group(function () {
        Route::post('clients/{clientId}/contracts', [ClientContractController::class, 'store']);
        Route::patch('contracts/{contractId}', [ClientContractController::class, 'update']);
        Route::post('contracts/{contractId}/pause', [ClientContractController::class, 'pause']);
        Route::post('contracts/{contractId}/complete', [ClientContractController::class, 'complete']);
    });

    // Invoices — read (all roles)
    Route::get('invoices', [ClientInvoiceController::class, 'index']);
    Route::get('invoices/{invoiceId}', [ClientInvoiceController::class, 'show']);

    // Invoices — write (owner, admin)
    Route::middleware('role:owner,admin')->group(function () {
        Route::post('invoices', [ClientInvoiceController::class, 'store']);
        Route::patch('invoices/{invoiceId}', [ClientInvoiceController::class, 'update']);
        Route::post('invoices/{invoiceId}/send', [ClientInvoiceController::class, 'send']);
        Route::post('invoices/{invoiceId}/mark-paid', [ClientInvoiceController::class, 'markPaid']);
        Route::post('invoices/{invoiceId}/cancel', [ClientInvoiceController::class, 'cancel']);
    });

    // Cost Allocations — read (all roles)
    Route::get('cost-allocations', [CostAllocationController::class, 'index']);

    // Cost Allocations — write (owner, admin)
    Route::middleware('role:owner,admin')->post('cost-allocations', [CostAllocationController::class, 'store']);

    // Reports — read (all roles)
    Route::get('client-reports/dashboard', [FinancialReportController::class, 'dashboard']);
    Route::get('client-reports/profitability', [FinancialReportController::class, 'profitability']);
});
