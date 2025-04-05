<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Constituency extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'sub_county_id'];

    /**
     * Get the sub-county that owns the constituency.
     */
    public function subCounty(): BelongsTo
    {
        return $this->belongsTo(SubCounty::class);
    }
}
