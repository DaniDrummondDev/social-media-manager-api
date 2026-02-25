<?php

declare(strict_types=1);

namespace App\Infrastructure\Publishing\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ListScheduledPostsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'string', 'in:pending,dispatched,publishing,published,failed,cancelled'],
            'provider' => ['sometimes', 'string', 'in:instagram,tiktok,youtube'],
            'campaign_id' => ['sometimes', 'uuid'],
            'from' => ['sometimes', 'date'],
            'to' => ['sometimes', 'date', 'after_or_equal:from'],
        ];
    }
}
