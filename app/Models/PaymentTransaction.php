<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentTransaction extends Model
{
    protected $fillable = [
        'payment_id', 'provider', 'merchant_reference', 'tracking_id', 'redirect_url',
        'amount', 'currency', 'status', 'payment_method', 'confirmation_code',
        'payment_account', 'status_description',
    ];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2'];
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
