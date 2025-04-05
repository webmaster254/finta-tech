<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Panel;
use App\Models\Branch;
use Filament\Facades\Filament;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Filament\Models\Contracts\HasName;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Filament\Models\Contracts\HasAvatar;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\HasTenants;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use BezhanSalleh\FilamentShield\Traits\HasPanelShield;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Rappasoft\LaravelAuthenticationLog\Traits\AuthenticationLoggable;

class User extends Authenticatable implements FilamentUser ,HasName, HasAvatar , HasTenants
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, HasPanelShield , AuthenticationLoggable;



    public function getFilamentName(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'first_name',
        'middle_name',
        'last_name',
        'created_by_id',
        'phone',
        'address',
        'city',
        'gender',
        'notes',
        'avatar_url',
        'photo'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];
    protected $appends = [
        'full_name'
    ];

    protected static function booted(): void
    {
        static::creating(static function ($model) {
           // $model->branch_id = Filament::getTenant()->id;
            $auth = Auth::id();
            $model->created_by_id = $auth;


        });

    }



 public function currentBranch()
 {
    return Filament::getTenant();
 }
    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class);
    }

    public function getTenants(Panel $panel): Collection
    {
        return $this->branches;
    }

    public function branch(): BelongsTo
   {
       return $this->belongsTo(Branch::class,);
   }



    public function canAccessTenant(Model $tenant): bool
    {
        return $this->branches()->whereKey($tenant)->exists();
    }


    public function getFilamentAvatarUrl(): ?string
    {
        return $this->avatar_url ? env('APP_URL') . Storage::url(($this->avatar_url)): null ;// default Filament avatar
    }




    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    public function canAccessPanel(Panel $panel): bool
    {


        if ( $panel->getId() === 'admin') {
             return $this->roles()->where('is_system',1)->exists() ;
        }

        return true;
    }



    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super_admin');
    }
  
   public function isAdmin(): bool
    {
        return $this->hasRole('admin') || $this->hasRole('super_admin');
    }

}
