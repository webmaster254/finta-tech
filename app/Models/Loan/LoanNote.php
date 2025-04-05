<?php

namespace App\Models\Loan;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'created_by_id',
        'loan_id',
        'name',
        'description',
    ];
    public $table = "loan_notes";

    public function created_by()
    {
        return $this->hasOne(User::class, 'id', 'created_by_id');
    }
}
