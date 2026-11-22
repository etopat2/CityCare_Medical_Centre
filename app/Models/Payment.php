<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    /** @use HasFactory<\Database\Factories\PaymentFactory> */
    use HasFactory;

    public const STATUSES = ['pending', 'partial', 'paid', 'refunded'];

    public const METHODS = ['cash', 'mobile_money', 'card', 'insurance'];

    protected $fillable = ['appointment_id', 'patient_id', 'amount', 'amount_paid', 'payment_method', 'status', 'reference_number', 'paid_at', 'received_by', 'notes'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'amount_paid' => 'decimal:2', 'paid_at' => 'datetime'];
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    public function getBalanceAttribute(): float
    {
        return max(0, (float) $this->amount - (float) $this->amount_paid);
    }
}
