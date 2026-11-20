<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Patient extends Model
{
    /** @use HasFactory<\Database\Factories\PatientFactory> */
    use HasFactory;

    protected $fillable = ['user_id', 'patient_number', 'first_name', 'last_name', 'gender', 'date_of_birth', 'phone', 'email', 'address', 'national_id', 'emergency_contact_name', 'emergency_contact_phone', 'blood_group', 'allergies'];

    protected function casts(): array
    {
        return ['date_of_birth' => 'date'];
    }

    protected static function booted(): void
    {
        static::created(function (Patient $patient): void {
            if (blank($patient->patient_number)) {
                $patient->forceFill(['patient_number' => 'CC-'.now()->year.'-'.str_pad((string) $patient->id, 5, '0', STR_PAD_LEFT)])->saveQuietly();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function consultations(): HasMany
    {
        return $this->hasMany(Consultation::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
