<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represents the prescription item domain record and its persistence relationships.
 */
class PrescriptionItem extends Model
{
    use HasFactory;

    public const STATUSES = ['pending', 'ready', 'dispensed', 'not_dispensed', 'out_of_stock'];

    protected $fillable = ['medication_order_id', 'medicine_id', 'quantity', 'unit_price', 'line_total', 'status', 'dispensed_by', 'dispensed_at'];

    /**
     * Defines how date, money, and boolean attributes are converted when the model is read from or written to storage.
     */
    protected function casts(): array
    {
        return ['quantity' => 'integer', 'unit_price' => 'decimal:2', 'line_total' => 'decimal:2', 'dispensed_at' => 'datetime'];
    }

    /**
     * Defines the order association as an Eloquent belongs-to link to the MedicationOrder model. It uses the 'medication_order_id' foreign key.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(MedicationOrder::class, 'medication_order_id');
    }

    /**
     * Defines the medicine association as an Eloquent belongs-to link to the Medicine model.
     */
    public function medicine(): BelongsTo
    {
        return $this->belongsTo(Medicine::class);
    }
}
