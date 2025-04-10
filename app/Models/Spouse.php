<?php

namespace App\Models;

use App\Models\Client;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Spouse extends Model
{
    use HasFactory;

    protected $table = 'spouses';
    
    protected $fillable = [
        'client_id',
        'name',
        'mobile',
        'email',
        'occupation',
        'id_number',
        'photo',
        'consent_signature',
        
        'consent_form'
    ];

    /**
     * Get the client that owns the spouse.
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
