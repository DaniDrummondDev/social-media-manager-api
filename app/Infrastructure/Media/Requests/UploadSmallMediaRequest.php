<?php

declare(strict_types=1);

namespace App\Infrastructure\Media\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

final class UploadSmallMediaRequest extends FormRequest
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
            'file' => [
                'required',
                'file',
                'max:10240',
                'mimes:jpg,jpeg,png,gif,mp4,mov,avi,webp',
            ],
            'checksum' => ['required', 'string', 'size:64'],
        ];
    }

    /**
     * SECURITY FIX (UPLOAD-001): Validate MIME type using magic bytes
     * 
     * @param Validator $validator
     * @return void
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($this->hasFile('file')) {
                /** @var \Illuminate\Http\UploadedFile $file */
                $file = $this->file('file');
                
                // Get MIME type from magic bytes (not from extension)
                $mimeType = $file->getMimeType();
                
                // Whitelist of allowed MIME types
                $allowedMimes = [
                    'image/jpeg',
                    'image/png',
                    'image/gif',
                    'image/webp',
                    'video/mp4',
                    'video/quicktime',
                    'video/x-msvideo',
                ];
                
                if (!in_array($mimeType, $allowedMimes, true)) {
                    $validator->errors()->add(
                        'file',
                        sprintf('Invalid file type. Detected: %s', $mimeType)
                    );
                }
                
                // SECURITY FIX (UPLOAD-002): Validate extension matches MIME type
                $extension = strtolower($file->getClientOriginalExtension());
                $expectedExtensions = [
                    'image/jpeg' => ['jpg', 'jpeg'],
                    'image/png' => ['png'],
                    'image/gif' => ['gif'],
                    'image/webp' => ['webp'],
                    'video/mp4' => ['mp4'],
                    'video/quicktime' => ['mov'],
                    'video/x-msvideo' => ['avi'],
                ];
                
                $validExtensions = $expectedExtensions[$mimeType] ?? [];
                if (!in_array($extension, $validExtensions, true)) {
                    $validator->errors()->add(
                        'file',
                        sprintf(
                            'File extension "%s" does not match MIME type "%s"',
                            $extension,
                            $mimeType
                        )
                    );
                }
            }
        });
    }
}
