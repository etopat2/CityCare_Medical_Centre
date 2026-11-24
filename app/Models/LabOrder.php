<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Represents the lab order domain record and its persistence relationships.
 */
class LabOrder extends Model
{
    public const STATUSES = ['pending_payment', 'queued', 'processing', 'completed', 'cancelled'];

    protected $fillable = ['patient_id', 'consultation_id', 'appointment_id', 'ordered_by', 'status', 'amount', 'collected_by', 'collected_at', 'completed_by', 'completed_at'];

    /**
     * Defines how date, money, and boolean attributes are converted when the model is read from or written to storage.
     */
    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'collected_at' => 'datetime', 'completed_at' => 'datetime'];
    }

    /**
     * Defines the patient association as an Eloquent belongs-to link to the Patient model. Soft-deleted linked records remain available for historical records.
     */
    public function patient(): BelongsTo { return $this->belongsTo(Patient::class)->withTrashed(); }

    /**
     * Defines the consultation association as an Eloquent belongs-to link to the Consultation model. Soft-deleted linked records remain available for historical records.
     */
    public function consultation(): BelongsTo { return $this->belongsTo(Consultation::class); }

    /**
     * Defines the appointment association as an Eloquent belongs-to link to the Appointment model. Soft-deleted linked records remain available for historical records.
     */
    public function appointment(): BelongsTo { return $this->belongsTo(Appointment::class)->withTrashed(); }

    /**
     * Defines the items association as an Eloquent one-to-many link to the LabOrderItem model.
     */
    public function items(): HasMany { return $this->hasMany(LabOrderItem::class); }

    /**
     * Defines the payment association as an Eloquent one-to-one link to the Payment model. It uses the 'lab_order_id' foreign key.
     */
    public function payment(): HasOne { return $this->hasOne(Payment::class, 'lab_order_id'); }
}
