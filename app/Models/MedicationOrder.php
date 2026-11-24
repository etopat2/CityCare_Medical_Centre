<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Represents the medication order domain record and its persistence relationships.
 */
class MedicationOrder extends Model
{
    use HasFactory;

    public const STATUSES = ['pending_review', 'pending_payment', 'ready_to_dispense', 'dispensed', 'cleared'];

    protected $fillable = ['consultation_id', 'patient_id', 'status', 'amount', 'reviewed_by', 'reviewed_at', 'cleared_by', 'cleared_at'];

    /**
     * Defines how date, money, and boolean attributes are converted when the model is read from or written to storage.
     */
    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'reviewed_at' => 'datetime', 'cleared_at' => 'datetime'];
    }

    /**
     * Defines the consultation association as an Eloquent belongs-to link to the Consultation model. Soft-deleted linked records remain available for historical records.
     */
    public function consultation(): BelongsTo
    {
        return $this->belongsTo(Consultation::class);
    }

    /**
     * Defines the patient association as an Eloquent belongs-to link to the Patient model. Soft-deleted linked records remain available for historical records.
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class)->withTrashed();
    }

    /**
     * Defines the items association as an Eloquent one-to-many link to the PrescriptionItem model.
     */
    public function items(): HasMany
    {
        return $this->hasMany(PrescriptionItem::class);
    }

    /**
     * Defines the payment association as an Eloquent one-to-one link to the Payment model. It uses the 'medication_order_id' foreign key.
     */
    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class, 'medication_order_id');
    }
}
