<?php

namespace App\Models\Loan;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'created_by_id',
        'loan_id',
        'name',
        'description',
        'size',
        'file',
    ];
    public $table = "loan_files";

    public function created_by()
    {
        return $this->belongsTo(User::class);
    }
}
