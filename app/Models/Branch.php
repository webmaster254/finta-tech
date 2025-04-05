<?php

namespace App\Models;

use App\Models\User;
use App\Models\Client;
use App\Models\Loan\Loan;
use App\Models\BankAccount;
use Illuminate\Database\Eloquent\Model;
use Filament\Models\Contracts\HasCurrentTenantLabel;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Branch extends Model implements HasCurrentTenantLabel
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'branch_code',
    ];
    
    protected static function booted()
    {
        static::creating(function ($branch) {
            // If branch_code is not set, generate it based on ID
            if (empty($branch->branch_code)) {
                // We'll set it to null now and update after creation when we have the ID
                $branch->branch_code = null;
            }
        });
        
        static::created(function ($branch) {
            // Now that we have an ID, generate the branch code if it wasn't set
            if (empty($branch->branch_code)) {
                // Special case for head office (ID 1)
                if ($branch->id === 1) {
                    $branch->branch_code = '000';
                } else {
                    // Format other branch IDs as 001, 002, etc.
                    $branch->branch_code = str_pad($branch->id, 3, '0', STR_PAD_LEFT);
                }
                $branch->save();
            }
        });
    }

    public function users()
    {
        return $this->belongsToMany(User::class);
    }



    public function clients()
    {
        return $this->hasMany(Client::class);
    }

    public function loans()
    {
        return $this->hasMany(Loan::class);
    }

    public function account()
    {
        return $this->hasMany(BankAccount::class);
    }

    public function getCurrentTenantLabel(): string
    {
        return 'Active Branch';
    }


}
