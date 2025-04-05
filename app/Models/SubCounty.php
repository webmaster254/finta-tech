<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubCounty extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'county_id'];
    protected $table = 'subcounty';
    /**
     * Get the county that owns the sub-county.
     */
    public function county(): BelongsTo
    {
        return $this->belongsTo(County::class);
    }

    /**
     * Get the towns for the sub-county.
     */
    public function towns(): HasMany
    {
        return $this->hasMany(Town::class);
    }
}
