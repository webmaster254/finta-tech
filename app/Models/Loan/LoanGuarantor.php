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


    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->middle_name . ' ' . $this->last_name;
    }

    public function title()
    {
        return $this->belongsTo(Title::class);
    }

    public static function getForm(){
        return [
                Hidden::make('created_by_id')
                         ->default(Auth::id()),
                Hidden::make('loan_id'),
                Select::make('client_relationship_id')
                    ->label('Relationship')
                    ->placeholder('Select Relationship')
                    ->options(
                        ClientRelationship::all()->pluck('name', 'id')
                    )
                    ->required(),
                Select::make('title_id')
                    ->placeholder('Select Title')
                    ->relationship('title','name'),
                TextInput::make('first_name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('middle_name')
                    ->maxLength(255),
                TextInput::make('last_name')
                    ->required()
                    ->maxLength(255),
                select::make('gender')
                    ->required()
                    ->options(Gender::class),
                Select::make('marital_status')
                    ->options(MaritalStatus::class),
                select::make('profession_id')
                    ->label('Profession')
                    ->options(Profession::all()->pluck('name', 'id'))
                    ->searchable(),
                TextInput::make('mobile')
                    ->required()
                    ->tel()
                    ->maxLength(255),
                TextInput::make('email')
                    ->email()
                    ->maxLength(255),
                DatePicker::make('dob')
                     ->required()
                    ->native(false),
                Textarea::make('address')
                    ->maxLength(65535),
                TextInput::make('city')
                    ->maxLength(255),
                TextInput::make('state')
                    ->maxLength(255),
                FileUpload::make('photo')
                    ->image()
                    ->avatar()
                    ->imageEditor()
                    ->circleCropper()
                    ->columnSpanFull(),
                TextInput::make('guaranteed_amount')
                    ->numeric()
                    ->required()
                    ->prefix(Currency::where('is_default', 1)->first()->symbol),
                Textarea::make('notes')
                    ->maxLength(65535)
                    ->columnSpanFull(),
        ];
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
