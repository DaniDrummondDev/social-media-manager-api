<?php

declare(strict_types=1);

namespace App\Infrastructure\Billing\Controllers;

use App\Application\Billing\DTOs\ProcessStripeWebhookInput;
use App\Application\Billing\Exceptions\StripeWebhookAlreadyProcessedException;
use App\Application\Billing\Exceptions\StripeWebhookInvalidSignatureException;
use App\Application\Billing\UseCases\ProcessStripeWebhookUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class StripeWebhookController
{
    public function handle(Request $request, ProcessStripeWebhookUseCase $useCase): JsonResponse
    {
        try {
            $useCase->execute(new ProcessStripeWebhookInput(
                payload: $request->getContent(),
                signature: $request->header('Stripe-Signature', ''),
            ));

            return new JsonResponse(['received' => true], 200);
        } catch (StripeWebhookAlreadyProcessedException) {
            return new JsonResponse(['received' => true], 200);
        } catch (StripeWebhookInvalidSignatureException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }
}
