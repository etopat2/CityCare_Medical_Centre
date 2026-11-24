<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Represents the lab test domain record and its persistence relationships.
 */
class LabTest extends Model
{
    protected $fillable = ['code', 'name', 'specimen', 'price', 'is_active'];

    /**
     * Defines how date, money, and boolean attributes are converted when the model is read from or written to storage.
     */
    protected function casts(): array
    {
        return ['price' => 'decimal:2', 'is_active' => 'boolean'];
    }

    /**
     * Defines the orderItems association as an Eloquent one-to-many link to the LabOrderItem model.
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(LabOrderItem::class);
    }
}
