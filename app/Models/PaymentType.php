<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentType extends Model
{
    use HasFactory;
    protected $table = "payment_types";
    protected $fillable = [
        'name',
        'active',
        'description',
        'is_online',
        'is_cash',
        'is_system',
        
    ];
}
