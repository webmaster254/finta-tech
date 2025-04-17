<?php

namespace App\Models\Loan;

use App\Models\User;
use App\Enums\Gender;
use App\Models\Title;
use App\Models\Client;
use App\Models\Currency;
use App\Models\Profession;
use App\Enums\MaritalStatus;
use App\Models\ClientRelationship;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LoanGuarantor extends Model
{
    use HasFactory;

    public $table = "loan_guarantors";
    protected $fillable = [
        'is_client',
        'created_by_id',
        'loan_id',
        'client_id',
        'title_id',
        'first_name',
        'middle_name',
        'last_name',
        'gender',
        'status',
        'marital_status',
        'country_id',
        'profession_id',
        'client_relationship_id',
        'mobile',
        'phone',
        'email',
        'dob',
        'id_number',
        'address',
        'city',
        'state',
        'zip',
        'employer',
        'photo',
        'notes',
        'guaranteed_amount',
        'joined_date',
    ];


    protected static function booted()
    {
        static::creating(function ($guarantor) {
            // If client field is set (from the form) and client_id is not set
            if (request()->has('client') && !$guarantor->client_id) {
                $guarantor->client_id = request()->input('client');
            }
            
            // If loan_id is not set but we're in a relationship context
            if (!$guarantor->loan_id && request()->route('record')) {
                $guarantor->loan_id = request()->route('record');
            }
        });
    }
    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->middle_name . ' ' . $this->last_name;
    }

    public function title()
    {
        return $this->belongsTo(Title::class);
    }

    public function created_by()
    {
        return $this->hasOne(User::class, 'id', 'created_by_id');
    }

    public function client()
    {
        return $this->hasOne(Client::class, 'id', 'client_id');
    }

    public function profession()
    {
        return $this->belongsTo(Profession::class);
    }

    public function country()
    {
        return $this->hasOne(Country::class, 'id', 'country_id');
    }

    public function client_relationship()
    {
        return $this->hasOne(ClientRelationship::class, 'id', 'client_relationship_id');
    }
}
