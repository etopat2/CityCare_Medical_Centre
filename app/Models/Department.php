<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Represents the department domain record and its persistence relationships.
 */
class Department extends Model
{
    /** @use HasFactory<\Database\Factories\DepartmentFactory> */
    use HasFactory;

    protected $fillable = ['name', 'description', 'is_active'];

    /**
     * Defines how date, money, and boolean attributes are converted when the model is read from or written to storage.
     */
    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /**
     * Defines the doctors association as an Eloquent one-to-many link to the Doctor model.
     */
    public function doctors(): HasMany
    {
        return $this->hasMany(Doctor::class);
    }
}
