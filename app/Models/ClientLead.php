<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Client;

class ClientLead extends Model
{
    use HasFactory;

    protected $table = 'client_leads';

    protected $fillable = [
        'client_id',
        'lead_source',
        'existing_client',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
