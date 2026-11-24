<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Records an approved non-cash waiver against an appointment consultation fee. */
class PaymentExemption extends Model
{
    public const REASONS = [
        'financial_hardship' => 'Financial hardship',
        'charity_care' => 'Charity or compassionate care',
        'community_outreach' => 'Community outreach offer',
        'service_recovery' => 'Service recovery',
        'approved_promotion' => 'Approved clinic offer',
        'other' => 'Other approved reason',
    ];

    protected $fillable = ['payment_id', 'amount', 'reason', 'details', 'exempted_by'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2'];
    }

    /** Returns the appointment charge that this approved waiver reduces. */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /** Returns the administrator or doctor who authorized the waiver. */
    public function exemptedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'exempted_by')->withTrashed();
    }
}
