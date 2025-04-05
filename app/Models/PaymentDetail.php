<?php

namespace App\Models;

use App\Models\PaymentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PaymentDetail extends Model
{
    use HasFactory;
    protected $table = "payment_details";
    protected $fillable = [
        'payment_type_id',
        'created_by_id',
        'transaction_type',
        'reference',
        'cheque_number',
        'receipt',
        'account_number',
        'bank_name',
        'routing_code',
        'description',
    ];

    public function payment_type()
    {
        return $this->hasOne(PaymentType::class, 'id', 'payment_type_id');
    }
}
