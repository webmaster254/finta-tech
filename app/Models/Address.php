<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    use HasFactory;
    protected $table = 'addresses';

    protected $fillable = [
        'client_id',
        'address_type',
        'country',
        'county',
        'sub_county',
        'ward',
        'village',
        'street',
        'landmark',
        'latitude',
        'longitude',
        'building',
        'floor_no',
        'house_no',
        'estate',
        'image',
        'image_description',
    ];

    /**
     * Get the client that owns the address.
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
