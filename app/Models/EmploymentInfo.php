<?php

namespace App\Models;

use App\Models\User;
use App\Models\Branch;
use App\Models\Client;
use App\Models\Profession;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EmploymentInfo extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'branch_id',
        'employer_name',
        'employment_type',
        'occupation',
        'designation',
        'working_since',
        'gross_income',
        'other_income',
        'expense',
        'net_income',
        'employment_letter',
        'pay_slip',
        'created_by_id',
    ];

    public static function booted(): void
    {
        static::creating(function ($model) {
            $model->branch_id = Filament::getTenant()->id;
            $auth = Auth::id();
            $model->created_by_id = $auth;
        });
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id', 'id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function profession(): BelongsTo
    {
        return $this->belongsTo(Profession::class);
    }
}
