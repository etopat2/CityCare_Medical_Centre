<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represents the payment transaction domain record and its persistence relationships.
 */
class PaymentTransaction extends Model
{
    protected $fillable = [
        'payment_id', 'provider', 'merchant_reference', 'tracking_id', 'redirect_url',
        'amount', 'currency', 'status', 'payment_method', 'confirmation_code',
        'payment_account', 'status_description',
    ];

    /**
     * Defines how date, money, and boolean attributes are converted when the model is read from or written to storage.
     */
    protected function casts(): array
    {
        return ['amount' => 'decimal:2'];
    }

    /**
     * Defines the payment association as an Eloquent belongs-to link to the Payment model.
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
