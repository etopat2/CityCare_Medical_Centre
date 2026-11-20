<?php

namespace App\Http\Requests;

use App\Services\AppointmentService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateAppointmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('appointment')) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'patient_id' => ['required', 'integer', 'exists:patients,id'],
            'doctor_id' => ['required', 'integer', Rule::exists('doctors', 'id')->where('is_active', true)],
            'appointment_date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'reason' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->hasAny(['patient_id', 'doctor_id', 'appointment_date', 'start_time'])) {
                return;
            }

            $error = app(AppointmentService::class)->slotError(
                $this->integer('doctor_id'),
                $this->integer('patient_id'),
                $this->string('appointment_date')->toString(),
                $this->string('start_time')->toString(),
                $this->route('appointment')->id,
            );

            if ($error !== null) {
                $validator->errors()->add('start_time', $error);
            }
        }];
    }
}
