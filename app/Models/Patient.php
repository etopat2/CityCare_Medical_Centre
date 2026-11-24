<?php

namespace App\Models;

use Database\Factories\PatientFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Represents the patient domain record and its persistence relationships.
 */
class Patient extends Model
{
    /** @use HasFactory<PatientFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = ['user_id', 'patient_number', 'first_name', 'last_name', 'gender', 'date_of_birth', 'phone', 'email', 'address', 'national_id', 'emergency_contact_name', 'emergency_contact_phone', 'blood_group', 'allergies'];

    /**
     * Defines how date, money, and boolean attributes are converted when the model is read from or written to storage.
     */
    protected function casts(): array
    {
        return ['date_of_birth' => 'date'];
    }

    /**
     * Assigns a stable clinic patient number after creation when no number was supplied.
     */
    protected static function booted(): void
    {
        static::created(function (Patient $patient): void {
            if (blank($patient->patient_number)) {
                $patient->forceFill(['patient_number' => 'CC-'.now()->year.'-'.str_pad((string) $patient->id, 5, '0', STR_PAD_LEFT)])->saveQuietly();
            }
        });
    }

    /**
     * Defines the user association as an Eloquent belongs-to link to the User model. Soft-deleted linked records remain available for historical records.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    /**
     * Defines the appointments association as an Eloquent one-to-many link to the Appointment model.
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * Defines the consultations association as an Eloquent one-to-many link to the Consultation model.
     */
    public function consultations(): HasMany
    {
        return $this->hasMany(Consultation::class);
    }

    /**
     * Defines the payments association as an Eloquent one-to-many link to the Payment model.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
