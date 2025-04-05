<?php

namespace App\Models;

use App\Enums\AssetStatus;
use App\Models\ChartOfAccount;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Asset extends Model
{
    use HasFactory;
    protected $table = 'assets';
    protected $fillable = [
        'created_by_id',
        'asset_type_id',
        'name',
        'purchase_date',
        'purchase_price',
        'replacement_value',
        'value',
        'life_span',
        'salvage_value',
        'serial_number',
        'bought_from',
        'purchase_year',
        'notes',
        'status',
        'active'
    ];
    protected $casts = [
        'status' => AssetStatus::class
    ];

    public function assetType()
    {
        return $this->belongsTo(AssetType::class);
    }
    public function chartOfAccountAsset()
    {
        return $this->belongsTo(ChartOfAccount::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }
}
