<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MpesaSTK extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $table = 'mpesa_s_t_k_s';
    protected $fillable = [
        'merchant_request_id',
        'checkout_request_id',
        'result_desc',
        'result_code',
        'transaction_id',
        'transaction_date',
        'amount',
        'msisdn',
        'business_shortcode',
        'status'
    ];
}
