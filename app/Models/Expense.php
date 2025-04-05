<?php

namespace App\Models;

use App\Casts\MoneyCast;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Expense extends Model
{
    use HasFactory;
    protected $table = 'expenses';
    protected $fillable = [
        'name',
        'created_by_id',
        'expense_chart_of_account_id',
        'currency_id',
        'asset_chart_of_account_id',
        'amount',
        'date',
        'recurring',
        'recur_frequncy',
        'recur_start_date',
        'recur_end_date',
        'recur_next_date',
        'recur_type',
        'notes',
        'description',
        'files'

    ];

    // protected $casts = [
    //     'amount' => MoneyCast::class,
    // ];

    public function asset_chart()
    {
        return $this->hasOne(ChartOfAccount::class,'id','asset_chart_of_account_id');
    }

    public function expense_chart()
    {
        return $this->hasOne(ChartOfAccount::class, 'id', 'expense_chart_of_account_id');
    }

    public function created_by()
    {
        return $this->hasOne(User::class, 'id', 'created_by_id');
    }
}
