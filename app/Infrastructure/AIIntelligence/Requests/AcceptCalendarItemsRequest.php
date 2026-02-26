<?php

declare(strict_types=1);

namespace App\Infrastructure\AIIntelligence\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class AcceptCalendarItemsRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'accepted_indexes' => ['required', 'array', 'min:1'],
            'accepted_indexes.*' => ['integer', 'min:0'],
        ];
    }
}
