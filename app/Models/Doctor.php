<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Represents the doctor domain record and its persistence relationships.
 */
class Doctor extends Model
{
    /** @use HasFactory<\Database\Factories\DoctorFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = ['user_id', 'department_id', 'specialization', 'license_number', 'phone', 'consultation_fee', 'bio', 'is_active'];

    /**
     * Defines how date, money, and boolean attributes are converted when the model is read from or written to storage.
     */
    protected function casts(): array
    {
        return ['consultation_fee' => 'decimal:2', 'is_active' => 'boolean'];
    }

    /**
     * Defines the user association as an Eloquent belongs-to link to the User model. Soft-deleted linked records remain available for historical records.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    /**
     * Defines the department association as an Eloquent belongs-to link to the Department model.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Defines the schedules association as an Eloquent one-to-many link to the DoctorSchedule model.
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(DoctorSchedule::class);
    }

    /**
     * Defines the appointments association as an Eloquent one-to-many link to the Appointment model.
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }
}
