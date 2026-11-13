<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('media.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'max:20480'],
            'collection' => ['nullable', 'string', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.uploaded' => 'The upload could not be received. Check the server upload limit ('.ini_get('upload_max_filesize').'), select the file again and retry. If it still fails, contact support.',
            'file.required' => 'Select a file to upload.',
            'file.file' => 'Select a valid file to upload.',
            'file.max' => 'Files must be 20 MB or smaller.',
        ];
    }
}
