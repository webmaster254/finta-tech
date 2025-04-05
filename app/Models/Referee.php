<?php

namespace App\Models;

use App\Models\Client;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Referee extends Model
{
    use HasFactory;

    protected $table = 'referees';
    
    protected $fillable = [
        'client_id',
        'name',
        'mobile',
        'email',
        'address',
        'relationship',
    ];

    /**
     * Get the client that owns the referee.
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
