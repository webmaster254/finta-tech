<?php

namespace App\Models;

use App\Models\Client;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ClientNextOfKins extends Model
{
    use HasFactory;
    protected $table = 'client_next_of_kins';
    protected $fillable = [
        'first_name',
        'middle_name',
        'created_by_id',
        'last_name',
        'client_id',
        'gender',
        'marital_status',
        'profession_id',
        'client_relationship_id',
        'mobile',
        'email',
        'address',
        'dob',
        'city',
        'photo',
        'notes',
    ];

    protected static function booted(): void
   {
       static::creating(static function ($model) {
          // $model->branch_id = Filament::getTenant()->id;
           $auth = Auth::id();
           $model->created_by_id = $auth;

       });

   }

    public function profession()
    {
        return $this->belongsTo(Profession::class);
    }
    public function client_relationship()
    {
        return $this->belongsTo(ClientRelationship::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

}
