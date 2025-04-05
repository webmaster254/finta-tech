<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class County extends Model
{
    use HasFactory;

    protected $fillable = ['name'];
    protected $table = 'county';

    /**
     * Get the sub-counties for the county.
     */
    public function subCounties(): HasMany
    {
        return $this->hasMany(SubCounty::class);
    }
}
