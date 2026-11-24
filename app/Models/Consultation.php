<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Represents the consultation domain record and its persistence relationships.
 */
class Consultation extends Model
{
    /** @use HasFactory<\Database\Factories\ConsultationFactory> */
    use HasFactory;

    protected $fillable = ['appointment_id', 'doctor_id', 'patient_id', 'symptoms', 'diagnosis', 'treatment_plan', 'prescription', 'notes', 'follow_up_date'];

    /**
     * Defines how date, money, and boolean attributes are converted when the model is read from or written to storage.
     */
    protected function casts(): array
    {
        return ['follow_up_date' => 'date'];
    }

    /**
     * Defines the appointment association as an Eloquent belongs-to link to the Appointment model. Soft-deleted linked records remain available for historical records.
     */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class)->withTrashed();
    }

    /**
     * Defines the doctor association as an Eloquent belongs-to link to the Doctor model. Soft-deleted linked records remain available for historical records.
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class)->withTrashed();
    }

    /**
     * Defines the patient association as an Eloquent belongs-to link to the Patient model. Soft-deleted linked records remain available for historical records.
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class)->withTrashed();
    }

    /**
     * Defines the medicationOrder association as an Eloquent one-to-one link to the MedicationOrder model.
     */
    public function medicationOrder(): HasOne
    {
        return $this->hasOne(MedicationOrder::class);
    }

    /**
     * Defines the labOrder association as an Eloquent one-to-one link to the LabOrder model.
     */
    public function labOrder(): HasOne
    {
        return $this->hasOne(LabOrder::class);
    }
}
