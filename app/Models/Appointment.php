<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Represents the appointment domain record and its persistence relationships.
 */
class Appointment extends Model
{
    /** @use HasFactory<\Database\Factories\AppointmentFactory> */
    use HasFactory, SoftDeletes;

    public const STATUSES = ['scheduled', 'confirmed', 'completed', 'cancelled', 'no_show'];

    protected $fillable = ['patient_id', 'doctor_id', 'department_id', 'consultation_fee', 'appointment_date', 'start_time', 'end_time', 'status', 'reason', 'cancellation_reason', 'booked_by'];

    /**
     * Defines how date, money, and boolean attributes are converted when the model is read from or written to storage.
     */
    protected function casts(): array
    {
        return ['appointment_date' => 'date', 'consultation_fee' => 'decimal:2'];
    }

    /**
     * Defines the patient association as an Eloquent belongs-to link to the Patient model. Soft-deleted linked records remain available for historical records.
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class)->withTrashed();
    }

    /**
     * Defines the doctor association as an Eloquent belongs-to link to the Doctor model. Soft-deleted linked records remain available for historical records.
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class)->withTrashed();
    }

    /**
     * Defines the department association as an Eloquent belongs-to link to the Department model. Soft-deleted linked records remain available for historical records.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Defines the booker association as an Eloquent belongs-to link to the User model. It uses the 'booked_by' foreign key. Soft-deleted linked records remain available for historical records.
     */
    public function booker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'booked_by')->withTrashed();
    }

    /**
     * Defines the consultation association as an Eloquent one-to-one link to the Consultation model.
     */
    public function consultation(): HasOne
    {
        return $this->hasOne(Consultation::class);
    }

    /**
     * Defines the payment association as an Eloquent one-to-one link to the Payment model.
     */
    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class)->where('billing_type', 'appointment');
    }

    /** Allows patient access to consultation records only when the quoted fee is zero or fully settled. */
    public function consultationFeeSettled(): bool
    {
        $fee = (float) ($this->consultation_fee ?? $this->doctor?->consultation_fee ?? 0);

        if ($fee <= 0) {
            return true;
        }

        return $this->payment?->isSettled() ?? false;
    }
}
