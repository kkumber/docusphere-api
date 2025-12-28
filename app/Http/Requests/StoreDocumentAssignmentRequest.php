<?php

namespace App\Http\Requests;

use App\Enums\RequestType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDocumentAssignmentRequest extends FormRequest
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
            'document_id' => ['required', 'exists:documents,id'],
            'request_type' => ['required', 'string', Rule::in(array_column(RequestType::cases(), 'value'))],
            'assigned_to' => ['required', 'exists:users,id'],
            'assigned_by' => ['required', 'exists:users,id'],
            'status_id' => ['required', 'exists:statuses,id'],
            'instructions' => ['string'],
            'due_date' => ['date'],            
        ];
    }
}
