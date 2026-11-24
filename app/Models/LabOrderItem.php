<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represents the lab order item domain record and its persistence relationships.
 */
class LabOrderItem extends Model
{
    protected $fillable = ['lab_order_id', 'lab_test_id', 'price', 'status', 'result', 'performed_by', 'performed_at'];

    /**
     * Defines how date, money, and boolean attributes are converted when the model is read from or written to storage.
     */
    protected function casts(): array { return ['price' => 'decimal:2', 'performed_at' => 'datetime']; }

    /**
     * Defines the order association as an Eloquent belongs-to link to the LabOrder model. It uses the 'lab_order_id' foreign key.
     */
    public function order(): BelongsTo { return $this->belongsTo(LabOrder::class, 'lab_order_id'); }

    /**
     * Defines the test association as an Eloquent belongs-to link to the LabTest model. It uses the 'lab_test_id' foreign key.
     */
    public function test(): BelongsTo { return $this->belongsTo(LabTest::class, 'lab_test_id'); }
}
