<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represents the doctor schedule domain record and its persistence relationships.
 */
class DoctorSchedule extends Model
{
    /** @use HasFactory<\Database\Factories\DoctorScheduleFactory> */
    use HasFactory;

    protected $fillable = ['doctor_id', 'day_of_week', 'start_time', 'end_time', 'slot_duration_minutes', 'is_active'];

    /**
     * Defines how date, money, and boolean attributes are converted when the model is read from or written to storage.
     */
    protected function casts(): array
    {
        return ['day_of_week' => 'integer', 'slot_duration_minutes' => 'integer', 'is_active' => 'boolean'];
    }

    /**
     * Defines the doctor association as an Eloquent belongs-to link to the Doctor model. Soft-deleted linked records remain available for historical records.
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class)->withTrashed();
    }
}
