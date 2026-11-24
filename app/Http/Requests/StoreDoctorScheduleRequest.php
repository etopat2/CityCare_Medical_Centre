<?php

namespace App\Http\Requests;

use App\Models\DoctorSchedule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Defines authorization and input validation rules for the store doctor schedule request workflow.
 */
class StoreDoctorScheduleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('schedules.manage') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'doctor_id' => ['required', 'integer', 'exists:doctors,id'],
            'day_of_week' => ['required', 'integer', 'between:0,6'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'slot_duration_minutes' => ['required', 'integer', 'between:10,240'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Adds cross-field validation checks that depend on the validated request context.
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->hasAny(['doctor_id', 'day_of_week', 'start_time', 'end_time'])) {
                return;
            }

            $overlaps = DoctorSchedule::query()
                ->where('doctor_id', $this->integer('doctor_id'))
                ->where('day_of_week', $this->integer('day_of_week'))
                ->where('start_time', '<', $this->input('end_time'))
                ->where('end_time', '>', $this->input('start_time'))
                ->exists();

            if ($overlaps) {
                $validator->errors()->add('start_time', 'This schedule overlaps another active or inactive schedule for the doctor.');
            }
        }];
    }
}
