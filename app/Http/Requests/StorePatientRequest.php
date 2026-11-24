<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Defines authorization and input validation rules for the store patient request workflow.
 */
class StorePatientRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('patients.create') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'gender' => ['required', Rule::in(['female', 'male', 'other'])],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'phone' => ['required', 'string', 'max:30', Rule::unique('patients', 'phone')->where('date_of_birth', $this->input('date_of_birth'))],
            'email' => ['nullable', 'email', 'max:255', Rule::requiredIf($this->boolean('portal_account')), Rule::when($this->boolean('portal_account'), Rule::unique('users', 'email'))],
            'address' => ['nullable', 'string', 'max:2000'],
            'national_id' => ['nullable', 'string', 'max:80'],
            'emergency_contact_name' => ['nullable', 'string', 'max:120'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:30'],
            'blood_group' => ['nullable', Rule::in(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'])],
            'allergies' => ['nullable', 'string', 'max:4000'],
            'portal_account' => ['sometimes', 'boolean'],
            'portal_password' => ['nullable', 'string', 'min:8', 'confirmed', Rule::requiredIf($this->boolean('portal_account'))],
        ];
    }
}
