<?php

declare(strict_types=1);

namespace App\Infrastructure\ClientFinance\Controllers;

use App\Application\ClientFinance\DTOs\GetFinancialDashboardInput;
use App\Application\ClientFinance\DTOs\GetProfitabilityReportInput;
use App\Application\ClientFinance\UseCases\GetFinancialDashboardUseCase;
use App\Application\ClientFinance\UseCases\GetProfitabilityReportUseCase;
use App\Infrastructure\Shared\Http\Resources\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class FinancialReportController
{
    public function dashboard(Request $request, GetFinancialDashboardUseCase $useCase): JsonResponse
    {
        $output = $useCase->execute(new GetFinancialDashboardInput(
            organizationId: $request->attributes->get('auth_organization_id'),
        ));

        return ApiResponse::success([
            'total_revenue_cents' => $output->totalRevenueCents,
            'total_cost_cents' => $output->totalCostCents,
            'profit_cents' => $output->profitCents,
            'margin_percent' => $output->marginPercent,
            'active_clients' => $output->activeClients,
            'active_contracts' => $output->activeContracts,
            'overdue_invoices' => $output->overdueInvoices,
            'draft_invoices' => $output->draftInvoices,
        ]);
    }

    public function profitability(Request $request, GetProfitabilityReportUseCase $useCase): JsonResponse
    {
        $output = $useCase->execute(new GetProfitabilityReportInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            clientId: $request->query('client_id'),
            referenceMonth: $request->query('reference_month'),
        ));

        return ApiResponse::success([
            'revenue_cents' => $output->revenueCents,
            'cost_cents' => $output->costCents,
            'profit_cents' => $output->profitCents,
            'margin_percent' => $output->marginPercent,
            'client_id' => $output->clientId,
            'reference_month' => $output->referenceMonth,
        ]);
    }
}
