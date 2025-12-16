<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDocumentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'tracking_no' => ['required', 'string', 'unique'],
            'title' => ['required', 'string'],
            'instructions' => ['required', 'string'],
            'category' => ['required', 'string', Rule::in(['advisory', 'endorsement', 'memorandum', 'unnumbered_memorandum'])],
            'originating_office' => ['required', 'string'],
            'request_type' => ['required', 'string', Rule::in(['for_signature', 'for_approval', 'for_information', 'for_endorsement', 'for_response'])],
            'uploaded_by' => ['required', 'integer', Rule::exists('users', 'id')],
            'status_id' => ['required', 'integer', Rule::exists('statuses', 'id')],
            'due_date' => ['required', 'date'],
        ];
    }
}
