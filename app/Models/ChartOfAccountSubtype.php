<?php

namespace App\Models;

use Filament\Panel;
use App\Models\Branch;
use App\Models\ChartOfAccount;
use App\Enums\ChartAccountType;
use Illuminate\Support\Collection;
use App\Enums\ChartAccountCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ChartOfAccountSubtype extends Model
{
    use HasFactory;



    protected $fillable = [
        'category',
        'type',
        'name',
        'description',
    ];

    protected $casts = [

        'category' => ChartAccountCategory::class,
        'type' => ChartAccountType::class,
    ];

    public function accounts(): HasMany
    {
        return $this->hasMany(ChartOfAccount::class, 'subtype_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class,'branch_id');
    }

    public function getTenants(Panel $panel): Collection
    {
        return $this->branch;
    }


    protected static function newFactory(): Factory
    {
        return ChartAccountSubtypeFactory::new();
    }
}
