<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'loan_id',
        'client_id',
        'message_description',
        'cost',
        'sent_by',
        'date_sent'
    ];

    protected $casts = [
        'date_sent' => 'datetime',
        'cost' => 'decimal:2'
    ];

    /**
     * Get the loan that the message belongs to.
     */
    public function loan()
    {
        return $this->belongsTo(\App\Models\Loan\Loan::class);
    }

    public function client()
    {
        return $this->belongsTo(\App\Models\Client::class);
    }

    public function sent_by()
    {
        return $this->belongsTo(User::class,'id');
    }
}
