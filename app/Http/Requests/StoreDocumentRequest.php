<?php

namespace App\Http\Requests;

use App\Enums\RequestType;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StoreDocumentRequest extends FormRequest
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
            'tracking_no' => ['required', 'string', 'unique:documents'],
            'title' => ['required', 'string'],
            'instructions' => ['required', 'string'],
            'category' => ['required', 'string', Rule::in(['advisory', 'endorsement', 'memorandum', 'unnumbered_memorandum'])],
            'originating_office' => ['required', 'string'],
            'request_type' => ['required', new Enum(RequestType::class)],
            'due_date' => ['required', 'date'],
            'file' => ['required', 'file', 'mimes:pdf', 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Please attach a file.',
            'file.mimes' => 'Unsupported file type.',
            'file.max' => 'File is too large.',
        ];
    }
}
