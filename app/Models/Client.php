<?php

namespace App\Models;

use Filament\Panel;
use App\Models\User;
use App\Enums\Status;
use App\Models\Title;
use App\Models\Branch;
use App\Models\Client;
use App\Models\Message;
use App\Models\Loan\Loan;
use App\Models\ClientFile;
use App\Models\ClientLead;
use App\Models\ClientType;
use App\Models\ClientAccount;
use App\Models\EmploymentInfo;
use Filament\Facades\Filament;
use App\Models\ClientNextOfKins;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Filament\Models\Contracts\HasName;
use Illuminate\Database\Eloquent\Model;
use Filament\Models\Contracts\HasAvatar;
use Illuminate\Notifications\Notifiable;
use Guava\FilamentDrafts\Concerns\HasDrafts;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Client extends Model implements HasName, HasAvatar
{
    use HasFactory;
    use Notifiable;
    use HasApiTokens;
    //use HasDrafts;
    protected $table = 'clients';
    protected $fillable = [
        'title_id',
        'first_name',
        'middle_name',
        'last_name',
        'account_number',
        'hashed_mobile',
        'gender',
        'marital_status',
        'profession_id',
        'client_type_id',
        'status',
        'created_by_id',
        'loan_officer_id',
        'mobile',
        'email',
        'pin_url',
        'dob',
        'city',
        'state',
        'photo',
        'notes',
        'address',
        'suggested_loan_limit',
        'id_front',
        'id_back',
        'signature',
        'is_published',
        'type_of_tech',
        'source_of_income',
        'education_level',
        'id_type',
        'id_number',
        'aka',
        'other_mobile_no',
        'kra_pin',
        'reg_form',
        'privacy_signature',
        'id_verified',
        'address_verified',
        'signature_confirmed',
        'referees_contacted',
    ];

   protected $casts = [
    'status' => Status::class,
    'id_verified' => 'boolean',
    'address_verified' => 'boolean',
    'signature_confirmed' => 'boolean',
    'referees_contacted' => 'boolean',
   ];

   protected static function booted(): void
   {
       static::creating(static function ($model) {
          $model->branch_id = Filament::getTenant()->id;
           $auth = Auth::id();
           $model->created_by_id = $auth;
           $model->hashed_mobile = hash('sha256', $model->mobile);
           
           // Generate account number
           $branch = Branch::find($model->branch_id);
           $branchCode = $branch->branch_code ?? '000';
           
           // Count clients in this branch and add 1 for the new client
           $clientCount = Client::where('branch_id', $model->branch_id)->count() + 1;
           
           // Format: 001000001 (9 characters - first 3 are branch code, last 6 are client count)
           $model->account_number = $branchCode . str_pad($clientCount, 6, '0', STR_PAD_LEFT);
       });

       static::updating(static function ($model) {
           $model->hashed_mobile = hash('sha256', $model->mobile);
       });
   }


   public function branch(): BelongsTo
   {
       return $this->belongsTo(Branch::class);
   }

   public function getTenants(Panel $panel): Collection
    {
        return $this->branch;
    }
public function sent_by(): BelongsTo
{
    return $this->belongsTo(User::class, 'sent_by');
}

public function client_lead()
{
    return $this->hasMany(ClientLead::class);
}
 public function addresses()
 {
     return $this->hasMany(Address::class,'client_id');
 }
 
 public function sms(): HasMany
 {
     return $this->hasMany(Message::class);
 }

 public function business()
{
    return $this->hasOne(Business::class);
}

public function employment()
{
    return $this->hasOne(EmploymentInfo::class);
}

public function spouse()
{
    return $this->hasOne(Spouse::class);
}

 public function referees()
 {
     return $this->hasMany(Referee::class);
 }
 

   public function isApprovalCompleted(): bool
    {
        foreach (collect($this->approvalStatus->steps ?? []) as $index => $item) {
            if ($item['process_approval_action'] === null || $item['process_approval_id'] === null) {
                return false;
            }
        }
        return true;
    }

    public function getStatus($clientId)
    {
        $client = Client::find($clientId);
        $status = $client->status;

        return  $status->name;
    }


    // Which fields can be used to filter the results through the query string
    public static array $allowedFilters = [
        'loan_officer_id',
        'branch_id'
    ];


    public function getFilamentAvatarUrl(): ?string
    {
        return $this->photo ? Storage::url($this->photo) : null ;// default Filament avatar
    }
    public function getFilamentName(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    protected $appends = ['full_name','name_id'];

    /**
     * Get the full name attribute.
     *
     * @return string
     */
    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->middle_name . ' ' . $this->last_name;
    }

/**
 * Generates a string that represents the name and ID of the object.
 *
 * @return string The generated name and ID string.
 */
    public function getNameIDAttribute()
    {
        return $this->first_name . ' ' . $this->middle_name . ' ' . $this->last_name.' (#'.$this->id.')';
    }
    /**
     * Retrieve the associated client type for the current instance.
     *
     * @return BelongsTo The associated client type.
     */
    public function client_type(): BelongsTo
    {
        return $this->belongsTo(ClientType::class);
    }


    public function title()
    {
        return $this->belongsTo(Title::class);
    }

/**
 * Retrieve the profession associated with the current instance.
 *
 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
 */
public function profession(): BelongsTo
{
    return $this->belongsTo(Profession::class);
}
public function loan_officer(): BelongsTo
{
    return $this->belongsTo(User::class);
}

/**
 * Retrieves the next of kin relationships for this client.
 *
 * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<ClientNextOfKins>
 */
public function next_of_kins(): HasMany
    {
        return $this->hasMany(ClientNextOfKins::class);
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class, 'client_id', 'id');
    }

    public function files(): HasMany
    {
        return $this->hasMany(ClientFile::class, 'client_id', 'id');
    }
    
    /**
     * Get the ordinary account associated with this client.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function account()
    {
        return $this->hasOne(ClientAccount::class);
    }

    public function changeStatus($status)
    {
        $this->update([
            'status' => $status
        ]);
        
        // If client is active and doesn't have an account yet, create one
        if ($status === 'active' && !$this->account()->exists()) {
            $this->update([
                'suggested_loan_limit' => 100000
            ]);
            $this->createOrdinaryAccount();
        }
    }

    public function activate()
    {
        $this->update([
            'status' => 'active',
            'closed_by_user_id' => null,
            'closed_on_date' => null,
        ]);
        
        // If client is activated and doesn't have an account yet, create one
        if (!$this->account()->exists()) {
            $this->createOrdinaryAccount();
        }
    }
    public function deactivate()
    {
        $this->update([
            'status' => 'inactive',
            'closed_by_user_id' => auth()->id(),
            'closed_on_date' => now(),
        ]);
    }
    public function changeLoanOfficer($loanOfficer)
    {
        $this->update(['loan_officer_id' => $loanOfficer]);
    }

    public function calculatePaymentHabitScore()
    {
        // Implement your payment habit score calculation logic here
        // This is a simplified example
        $score =0;
        $latestLoan = $this->loans()->whereIn('status', ['closed', 'active','rescheduled'])->latest()->first();
        if (!$latestLoan) return 500; // Default score for new clients

        $repayments = $latestLoan->repayment_schedules;
        $totalPayments = $repayments->filter(function ($repayment) {
            return !is_null($repayment->paid_by_date);
                     })->count();
        $onTimePayments = $repayments->filter(function ($repayment) {
            return $repayment->paid_by_date !== null &&
                   $repayment->paid_by_date <= $repayment->due_date;
        })->count();
         if(!$totalPayments)
         {
            $score = 0;
         }else {
            $score = number_format(($onTimePayments / $totalPayments) * 100, 2);
         }
        return $score;
    }

    public function calculateSuggestedLoanLimit($score)
    {
        // Implement your loan limit calculation logic here
        // This is a simplified example
       $latestLoan = $this->loans()->whereIn('status', ['closed', 'active',rescheduled])->latest()->first();
        $baseLimit = $latestLoan ? $latestLoan->approved_amount : 10000;


        $completedLoans = $this->loans()->where('status', 'closed')->count();


        if(!$completedLoans)
         {
            if($score<50)
            {
                $increaseAmount = 9000 * ($score / 100) ;
                $suggestedLimit = $baseLimit + $increaseAmount;
            }else if($score<70)
            {
              $increaseAmount =10000 * ($score / 100) ;
                $suggestedLimit = $baseLimit + $increaseAmount;
            }else
            {
                $increaseAmount = 8000 * ($score / 100) ;
                $suggestedLimit = $baseLimit + $increaseAmount;

            }
         }else {
            //$suggestedLimit = $baseLimit * (1 + ($score / 100)) * (1 + ($completedLoans / 10));

            if($score<50)
            {
                 $increaseAmount =8000 * (($score / 100) + ($completedLoans / 10)) ;
                $suggestedLimit = $baseLimit + $increaseAmount;
            }
            else if($score<70)
            {
              $increaseAmount =9000 * (($score / 100) + ($completedLoans / 10)) ;
                $suggestedLimit = $baseLimit + $increaseAmount;
            }
            else
            {
                //$increaseAmount = 8000 * (($score / 100) + ($completedLoans / 10)) ;
                $suggestedLimit = $baseLimit + 8000;

            }

        }
        $this->update(['suggested_loan_limit' => $suggestedLimit]);



        return round($suggestedLimit, -2); // Round to nearest 100
    }

    /**
     * Create an ordinary account for the client.
     *
     * @return ClientAccount
     */
    public function createOrdinaryAccount(): ClientAccount
    {
        // Check if account already exists
        if ($account = $this->account()->first()) {
            return $account;
        }
        
        // Create the account
        return $this->account()->create([
            'balance' => 0.00,
            'available_balance' => 0.00,
            'hold_amount' => 0.00,
            'currency_code' => 'KES', // Default currency
            'status' => 'active',
            'activated_at' => now(),
            'closed_by_id' => null,
            'closed_at' => null,
            'notes' => 'Ordinary account for tracking client payments and transactions',
        ]);
    }
}
