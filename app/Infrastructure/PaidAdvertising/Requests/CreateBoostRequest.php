<?php

declare(strict_types=1);

namespace App\Infrastructure\PaidAdvertising\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateBoostRequest extends FormRequest
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
            'scheduled_post_id' => ['required', 'string'],
            'ad_account_id' => ['required', 'string'],
            'audience_id' => ['required', 'string'],
            'budget_amount_cents' => ['required', 'integer', 'min:100'],
            'budget_currency' => ['required', 'string', 'size:3'],
            'budget_type' => ['required', 'string', 'in:daily,lifetime'],
            'duration_days' => ['required', 'integer', 'min:1', 'max:365'],
            'objective' => ['required', 'string', 'in:reach,engagement,traffic,conversions'],
        ];
    }
}
