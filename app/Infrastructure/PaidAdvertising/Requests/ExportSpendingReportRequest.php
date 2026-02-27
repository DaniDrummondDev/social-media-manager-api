<?php

declare(strict_types=1);

namespace App\Infrastructure\PaidAdvertising\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ExportSpendingReportRequest extends FormRequest
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
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from'],
            'format' => ['required', 'string', 'in:csv,pdf'],
        ];
    }
}
