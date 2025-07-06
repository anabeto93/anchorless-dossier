<?php

namespace App\Http\Requests;

use App\DTOs\StoreFileMetadataDTO;
use Illuminate\Validation\Rules\File;
use Illuminate\Foundation\Http\FormRequest;

class UploadFileFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'file' => ['required', File::types(['application/pdf', 'image/png', 'image/jpg', 'image/jpeg'])->max(4 * 1024)]
        ];
    }

    public function getData(): StoreFileMetadataDTO
    {
        return StoreFileMetadataDTO::fromRequest($this);
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Please select a file to upload.',
            'file.max' => 'The file must not be greater than 4MB.',
            'file.mimes' => 'The file must be a PDF, PNG, JPG, or JPEG.',
        ];
    }
}
