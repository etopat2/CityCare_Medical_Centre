<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Represents the payment domain record and its persistence relationships.
 */
class Payment extends Model
{
    /** @use HasFactory<\Database\Factories\PaymentFactory> */
    use HasFactory;

    public const STATUSES = ['pending', 'partial', 'paid', 'refunded'];

    public const METHODS = ['cash', 'mobile_money', 'card', 'insurance'];

    protected $fillable = ['appointment_id', 'medication_order_id', 'lab_order_id', 'billing_type', 'patient_id', 'amount', 'amount_paid', 'amount_waived', 'payment_method', 'status', 'reference_number', 'paid_at', 'received_by', 'notes'];

    /**
     * Defines how date, money, and boolean attributes are converted when the model is read from or written to storage.
     */
    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'amount_paid' => 'decimal:2', 'amount_waived' => 'decimal:2', 'paid_at' => 'datetime'];
    }

    /**
     * Defines the appointment association as an Eloquent belongs-to link to the Appointment model. Soft-deleted linked records remain available for historical records.
     */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class)->withTrashed();
    }

    /**
     * Defines the patient association as an Eloquent belongs-to link to the Patient model. Soft-deleted linked records remain available for historical records.
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class)->withTrashed();
    }

    /**
     * Defines the receiver association as an Eloquent belongs-to link to the User model. It uses the 'received_by' foreign key. Soft-deleted linked records remain available for historical records.
     */
    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by')->withTrashed();
    }

    /**
     * Defines the medicationOrder association as an Eloquent belongs-to link to the MedicationOrder model.
     */
    public function medicationOrder(): BelongsTo
    {
        return $this->belongsTo(MedicationOrder::class);
    }

    /**
     * Defines the labOrder association as an Eloquent belongs-to link to the LabOrder model. It uses the 'lab_order_id' foreign key.
     */
    public function labOrder(): BelongsTo
    {
        return $this->belongsTo(LabOrder::class, 'lab_order_id');
    }

    /**
     * Resolves the appointment linked directly or through a medication or laboratory order.
     */
    public function getVisitAppointmentAttribute(): ?Appointment
    {
        return $this->appointment ?? $this->medicationOrder?->consultation?->appointment ?? $this->labOrder?->appointment;
    }

    /**
     * Defines the transactions association as an Eloquent one-to-many link to the PaymentTransaction model.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    /** Records the approved non-cash exemption, separately from receipt transactions. */
    public function exemption(): HasOne
    {
        return $this->hasOne(PaymentExemption::class);
    }

    /**
     * Calculates the unpaid remainder and clamps it at zero to prevent a negative balance.
     */
    public function getBalanceAttribute(): float
    {
        return max(0, (float) $this->amount - (float) $this->amount_paid - (float) $this->amount_waived);
    }

    /** Confirms both the recorded state and the calculated balance indicate full settlement. */
    public function isSettled(): bool
    {
        return $this->status === 'paid' && $this->balance <= 0;
    }
}
