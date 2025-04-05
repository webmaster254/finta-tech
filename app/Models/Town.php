<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Town extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'sc_id'];
    protected $table = 'towns';
    /**
     * Get the sub-county that owns the town.
     */
    public function subCounty(): BelongsTo
    {
        return $this->belongsTo(SubCounty::class);
    }

   
}