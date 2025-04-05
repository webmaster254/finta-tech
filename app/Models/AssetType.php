<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetType extends Model
{
    use HasFactory;
    protected $table = 'asset_types';
    public $timestamps = false;
    protected $fillable = [
        'name',
        'type',
        'chart_of_account_fixed_asset_id',
        'chart_of_account_asset_id',
        'chart_of_account_contra_asset_id',
        'chart_of_account_expense_id',
        'chart_of_account_liability_id',
        'chart_of_account_income_id',
        'notes',
    ];

    public function chartOfAccountAsset()
    {
        return $this->belongsTo(ChartOfAccount::class, 'chart_of_account_asset_id');
    }

    public function chartOfAccountFixedAsset()
    {
        return $this->belongsTo(ChartOfAccount::class, 'chart_of_account_fixed_asset_id');
    }

    public function chartOfAccountContraAsset()
    {
        return $this->belongsTo(ChartOfAccount::class, 'chart_of_account_contra_asset_id');
    }


    public function chartOfAccountExpense()
    {
        return $this->belongsTo(ChartOfAccount::class, 'chart_of_account_expense_id');
    }

    public function chartOfAccountLiability()
    {
        return $this->belongsTo(ChartOfAccount::class, 'chart_of_account_liability_id');
    }

    public function chartOfAccountIncome()
    {
        return $this->belongsTo(ChartOfAccount::class, 'chart_of_account_income_id');
    }

    public function asset()
    {
        return $this->hasMany(Asset::class);
    }


}
