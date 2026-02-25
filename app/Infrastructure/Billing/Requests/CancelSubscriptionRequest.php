<?php

declare(strict_types=1);

namespace App\Infrastructure\Billing\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CancelSubscriptionRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'reason' => ['sometimes', 'string', 'max:500'],
            'feedback' => ['sometimes', 'string', 'in:too_expensive,missing_features,switched_service,too_complex,other'],
        ];
    }
}
