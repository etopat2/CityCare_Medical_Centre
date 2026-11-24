<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Represents the medicine domain record and its persistence relationships.
 */
class Medicine extends Model
{
    use HasFactory;

    protected $fillable = ['sku', 'name', 'strength', 'dosage_form', 'unit_price', 'stock_quantity', 'reserved_quantity', 'reorder_level', 'is_active'];

    /**
     * Defines how date, money, and boolean attributes are converted when the model is read from or written to storage.
     */
    protected function casts(): array
    {
        return ['unit_price' => 'decimal:2', 'stock_quantity' => 'integer', 'reserved_quantity' => 'integer', 'reorder_level' => 'integer', 'is_active' => 'boolean'];
    }

    /**
     * Defines the prescriptionItems association as an Eloquent one-to-many link to the PrescriptionItem model.
     */
    public function prescriptionItems(): HasMany
    {
        return $this->hasMany(PrescriptionItem::class);
    }

    /**
     * Returns physical stock less units reserved for reviewed prescriptions.
     */
    public function getAvailableQuantityAttribute(): int
    {
        return max(0, $this->stock_quantity - $this->reserved_quantity);
    }
}
