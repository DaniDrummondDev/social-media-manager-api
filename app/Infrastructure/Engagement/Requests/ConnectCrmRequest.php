<?php

declare(strict_types=1);

namespace App\Infrastructure\Engagement\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ConnectCrmRequest extends FormRequest
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
            'provider' => ['required', 'string', 'in:hubspot,rdstation,pipedrive,salesforce,activecampaign'],
        ];
    }
}
