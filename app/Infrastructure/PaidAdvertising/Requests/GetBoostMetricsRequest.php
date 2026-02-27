<?php

declare(strict_types=1);

namespace App\Infrastructure\PaidAdvertising\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class GetBoostMetricsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'period' => ['sometimes', 'string', 'in:hourly,daily,weekly,lifetime'],
        ];
    }
}
