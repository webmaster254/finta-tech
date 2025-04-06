<?php

namespace App\Models;

use Filament\Panel;
use App\Models\User;
use App\Enums\Industry;
use App\Enums\Ownership;
use App\Enums\BusinessType;
use Filament\Facades\Filament;
use App\Models\BusinessOverview;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Business extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'registration_number',
        'branch_id',
        'client_id',
        'business_type',
        'description',
        'industry',
        'location',
        'ownership',
        'premise_ownership',
        'employees',
        'sector',
        'major_products',
        'major_suppliers',
        'major_customers',
        'major_competitors',
        'strengths',
        'weaknesses',
        'opportunities',
        'threats',
        'mitigations',
        'insurance',
        'trading_license',
        'business_permit',
        'certificate_of_incorporation',
        'health_certificate',
        'establishment_date',
        'business_overview',
        'record_maintained',
        'assessed_by',
        'assessed_date',
        'status',
    ];

    protected $casts = [
        'establishment_date' => 'date',
        ];

    protected static function booted(): void
    {
        static::creating(static function ($model) {
            $model->branch_id = Filament::getTenant()->id;
            $auth = Auth::id();
            $model->assessed_by = $auth;
            $model->assessed_date = now();
        });
    }
    /**
     * Get the clients associated with this business.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id', 'id');
    }

    public function business_type(): BelongsTo
    {
        return $this->belongsTo(BusinessType::class);
    }

    public function industry(): BelongsTo
    {
        return $this->belongsTo(Industry::class);
    }

    public function ownership(): BelongsTo
    {
        return $this->belongsTo(Ownership::class);
    }

    public function assessed_by(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function business_overview()
    {
        return $this->hasOne(BusinessOverview::class);
    }
}
