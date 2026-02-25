<?php

declare(strict_types=1);

namespace App\Infrastructure\Billing\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateCheckoutRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'plan_slug' => ['required', 'string', 'max:50'],
            'billing_cycle' => ['required', 'string', 'in:monthly,yearly'],
            'success_url' => ['required', 'url', 'max:2000'],
            'cancel_url' => ['required', 'url', 'max:2000'],
        ];
    }
}
