<?php

namespace App\Models;

use App\Models\Town;
use App\Models\County;
use App\Models\SubCounty;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Address extends Model
{
    use HasFactory;
    protected $table = 'addresses';

    protected $fillable = [
        'client_id',
        'address_type',
        'country',
        'county_id',
        'sub_county_id',
        'ward_id',
        'village',
        'street',
        'landmark',
        'latitude',
        'longitude',
        'location',
        'building',
        'floor_no',
        'house_no',
        'estate',
        'image',
        'image_description',
    ];

    protected $casts = [
        'location' => 'array',
       ];

    /**
     * Get the client that owns the address.
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function counties(): BelongsTo
    {
        return $this->belongsTo(County::class);
    }

    public function subCounties(): BelongsTo
    {
        return $this->belongsTo(SubCounty::class);
    }

    public function towns(): BelongsTo
    {
        return $this->belongsTo(Town::class);
    }
}
