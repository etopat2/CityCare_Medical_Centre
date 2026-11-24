<?php

namespace App\Http\Requests;

use App\Models\Consultation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Defines authorization and input validation rules for the store consultation request workflow.
 */
class StoreConsultationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', [Consultation::class, $this->route('appointment')]) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'symptoms' => ['required', 'string', 'max:5000'],
            'diagnosis' => ['required', 'string', 'max:5000'],
            'treatment_plan' => ['nullable', 'string', 'max:5000'],
            'prescription' => ['nullable', 'string', 'max:5000'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'follow_up_date' => ['nullable', 'date', 'after_or_equal:today'],
            'prescriptions' => ['nullable', 'array', 'max:20'],
            'prescriptions.*.medicine_id' => ['required', 'integer', 'distinct', Rule::exists('medicines', 'id')->where('is_active', true)],
            'prescriptions.*.quantity' => ['required', 'integer', 'min:1', 'max:10000'],
            'lab_tests' => ['nullable', 'array', 'max:20'],
            'lab_tests.*' => ['required', 'integer', 'distinct', Rule::exists('lab_tests', 'id')->where('is_active', true)],
        ];
    }

    /**
     * Normalizes incoming values before the validator evaluates them.
     */
    protected function prepareForValidation(): void
    {
        $prescriptions = collect($this->input('prescriptions', []))
            ->filter(fn ($row): bool => is_array($row) && filled($row['medicine_id'] ?? null))
            ->values()
            ->all();

        $this->merge(['prescriptions' => $prescriptions]);
    }

    /** Adds authorization checks for lab referrals after the individual submitted fields have been validated. */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if ($this->filled('lab_tests') && ! $this->user()?->can('lab-orders.create')) {
                $validator->errors()->add('lab_tests', 'You do not have permission to place laboratory referrals.');
            }
        });
    }
}
