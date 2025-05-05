<?php

namespace App\Filament\Resources;

use layout;
use Filament\Forms;
use App\Models\Fund;
use App\Models\User;
use Filament\Tables;
use App\Enums\Status;
use App\Models\Client;
use Filament\Forms\Get;
use Filament\Forms\Set;
use App\Models\Currency;
use Filament\Forms\Form;
use App\Enums\LoanStatus;
use App\Models\Loan\Loan;
use App\Models\ClientType;
use Filament\Tables\Table;
use App\Models\BankAccount;
//use App\Events\LoanTransaction;
use App\Models\PaymentType;
use Illuminate\Support\Str;
use App\Events\LoanDisbursed;
use App\Events\LoanRepayment;
use App\Models\ChartOfAccount;
use Filament\Facades\Filament;
use Illuminate\Support\Carbon;
use App\Enums\CollateralStatus;
use App\Events\LoanUndisbursed;
use App\Jobs\UndisburseLoanJob;
use App\Models\Loan\LoanCharge;
use App\Events\LoanLinkedCharge;
use App\Models\Loan\LoanProduct;
use App\Models\Loan\LoanPurpose;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use App\Policies\Loan\LoanPolicy;
use App\Models\ClientRelationship;
use App\Models\Loan\LoanGuarantor;
use Illuminate\Support\Facades\DB;
use Filament\Forms\Components\Card;
use Filament\Forms\Components\Grid;
use Filament\Tables\Actions\Action;
use Filament\Tables\Filters\Filter;
use Illuminate\Support\Facades\Auth;
use App\Jobs\DisburseApprovedLoanJob;
use App\Models\Loan\LoanTransaction ;
use Cheesegrits\FilamentPhoneNumbers;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Wizard;
use App\Filament\Exports\LoanExporter;
use App\Filament\Imports\LoanImporter;
use Filament\Forms\Components\Section;
use Filament\Support\Enums\ActionSize;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Actions\BulkAction;
//use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Resources\LoanResource;
use Brick\PhoneNumber\PhoneNumberFormat;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\Split;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Actions\ExportAction;
use Filament\Tables\Actions\ImportAction;
use Filament\Tables\Filters\QueryBuilder;
use Filament\Tables\Filters\SelectFilter;
use Filament\Forms\Components\Placeholder;
use Illuminate\Contracts\Support\Htmlable;
use Filament\Infolists\Components\Fieldset;

use Filament\Actions\Action as filteraction;
use Filament\Infolists\Components\TextEntry;
use Filament\Tables\Columns\Summarizers\Sum;
use Illuminate\Database\Eloquent\Collection;
use Filament\Tables\Actions\ExportBulkAction;
use App\Filament\Resources\LoanResource\Pages;
use Illuminate\Validation\ValidationException;
use RectorPrefix202411\React\Dns\Model\Record;
use Filament\Tables\Actions\Action as TableAction;
use Filament\Tables\Columns\Summarizers\Summarizer;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\LoanResource\RelationManagers;
use App\Models\Loan\LoanTransaction as loanTransactions ;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Filament\Infolists\Components\Section as InfolistSection;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Filament\GlobalSearch\Actions\Action as globalSearchAction;
use Filament\Infolists\Components\Actions\Action as InfolistAction;
use Malzariey\FilamentDaterangepickerFilter\Fields\DateRangePicker;
use Filament\Tables\Filters\QueryBuilder\Constraints\DateConstraint;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;
use Filament\Tables\Filters\QueryBuilder\Constraints\SelectConstraint;
use App\Filament\Resources\LoanResource\RelationManagers\FilesRelationManager;
use App\Filament\Resources\LoanResource\RelationManagers\NotesRelationManager;
use App\Filament\Resources\LoanResource\RelationManagers\ChargesRelationManager;
use App\Filament\Resources\LoanResource\RelationManagers\CollateralRelationManager;
use App\Filament\Resources\LoanResource\RelationManagers\GuarantorsRelationManager;
use App\Filament\Resources\LoanResource\RelationManagers\TransactionsRelationManager;
use App\Filament\Resources\LoanResource\RelationManagers\RepaymentSchedulesRelationManager;

class LoanResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = Loan::class;

    protected static ?string $navigationIcon = null;
    protected static ?string $navigationLabel = 'View Loans';
    protected static ?string $navigationGroup = 'Loans Management';
    protected static ?int $navigationSort = 1;
    protected static ?string $tenantOwnershipRelationshipName = 'branch';

    public static function getNavigationBadge(): ?string
{
    return static::getModel()::where('status', 'submitted')->count();
}

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Wizard::make()
                      ->columnSpanFull()
                ->schema([
                    Wizard\Step::make('General Loan Information')
                        ->description('Enter loan information')
                        ->schema(self::getLoanInformation()),
                    Wizard\Step::make('Guarantors Information')
                        ->description('Enter guarantors information')
                        ->schema(self::getGuarantorsInformation()),
                    Wizard\Step::make('Collateral Information')
                        ->description('Enter collateral information')
                        ->schema(self::getCollateralInformation()),
                    Wizard\Step::make('Files Information')
                        ->description('Enter files information')
                        ->schema(self::getFilesInformation()),
                    
                ])             




                
            ]);
    }

    public static function getLoanInformation(): array
    {
        return [
            Card::make()->schema([

                Section::make('General Loan Details')->schema([

                    Forms\Components\Hidden::make('currency_id')
                        ->default(Currency::where('is_default', 1)->value('id')),


                        Select::make('client_type_id')
                            ->label('Client Type')
                            ->searchable()
                            ->options(ClientType::all()->pluck('name', 'id'))
                                ->live()
                                ->afterStateUpdated(fn (Select $component) => $component
                                    ->getContainer()
                                    ->getComponent('dynamicClientTypeFields')
                                    ->getChildComponentContainer()
                                    ->fill()),
                                    Grid::make(1)
                                    ->schema(fn (Get $get): array => match ($get('client_type_id')) {
                                        '1' => [
                                            Forms\Components\Select::make('account_number')
                                                ->label('Client Account Number')
                                                ->searchable()
                                                ->preload()
                                                ->live()
                                                ->options(function (Get $get){

                                                     $client = Client::where('client_type_id', '=', $get('client_type_id'))
                                                                  ->where('status', '=', 'active')
                                                                  ->whereDoesntHave('loans', function ($query) {
                                                                    $query->where('status', 'active')
                                                                    ->orWhere('status','pending')
                                                                    ->orWhere('status','approved');
                                                                })
                                                                 ->selectRaw('*, CONCAT(first_name, " ", last_name) AS name')
                                                                 ->pluck('account_number', 'account_number');


                                                    return $client;
                                            })
                                            ->afterStateUpdated(function (Set $set, Get $get)  {
                                                $client = Client::where('account_number',$get('account_number'))
                                                            ->selectRaw('*, CONCAT(first_name, " ", last_name) AS name')
                                                            ->first();
                                                if ($client) {
                                                    $set('client_id', $client->id);
                                                    $set('client_name',$client->name);
                                                } else{
                                                    $set('client_id', null);
                                                    $set('client_name',null);
                                                };
                                            })

                                        ],

                                        default => [],

                                    })
                                    ->key('dynamicClientTypeFields')->columns(2),
                                Forms\Components\Hidden::make('client_id'),
                                Forms\Components\TextInput::make('client_name'),
                                Forms\Components\Select::make('loan_product_id')
                                ->label('Loan Product')
                                   ->options(LoanProduct::all()->pluck('name', 'id'))
                                    ->live()
                                    ->reactive()
                                    ->afterStateUpdated(function (Set $set, Get $get)  {
                                        $loanProduct = LoanProduct::where('id',$get('loan_product_id'))->first();
                                        if ($loanProduct) {
                                            $set('principal', $loanProduct->default_principal);
                                            $set('applied_amount', $loanProduct->default_principal);
                                            $set('interest_rate', $loanProduct->default_interest_rate);
                                            $set('loan_term', $loanProduct->default_loan_term);
                                            $set('repayment_frequency', $loanProduct->repayment_frequency);
                                            $set('repayment_frequency_type', $loanProduct->repayment_frequency_type);
                                            $set('interest_rate_type', $loanProduct->interest_rate_type);
                                            $set('loan_transaction_processing_strategy_id', $loanProduct->loan_transaction_processing_strategy_id);
                                            $set('interest_methodology', $loanProduct->interest_methodology);
                                            $set('amortization_method', $loanProduct->amortization_method);
                                            $set('auto_disburse', $loanProduct->auto_disburse);
                                            $set('installment_multiple_of', $loanProduct->installment_multiple_of);
                                            $set('chart_of_account_id',$loanProduct->fund_source_chart_of_account_id);
                                        };


                                    })
                                    ->required(),
                                ])->columns(2),




                Section::make('Loan Terms')->schema([
                        Forms\Components\TextInput::make('principal')
                            ->numeric()
                            ->live()
                            ->required()
                            ->prefix('KES')
                            ->afterStateUpdated(function (Set $set, Get $get)  {
                                $set('applied_amount',$get('principal'));
                            }),
                        Forms\Components\Hidden::make('chart_of_account_id')
                            ->label('Fund Account')
                            ->required(),
                        Forms\Components\TextInput::make('loan_term')
                            ->required()
                            ->readOnly()
                            ->numeric(),
                        Forms\Components\TextInput::make('repayment_frequency')
                            ->required()
                            ->readOnly()
                            ->numeric(),
                        Forms\Components\TextInput::make('repayment_frequency_type')
                            ->required()
                            ->readOnly()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('interest_rate')
                            ->required()
                            ->readOnly()
                            ->numeric(),
                        Forms\Components\TextInput::make('interest_rate_type')
                            ->required()
                            ->readOnly()
                            ->maxLength(255),
                        Forms\Components\DatePicker::make('expected_disbursement_date')
                            ->native(false)
                            ->default(Carbon::now())
                            ->required(),
                        Forms\Components\DatePicker::make('expected_first_payment_date')
                            ->native(false)
                            ->default(Carbon::now()->addDay())//add 1 day
                            ->required(),
                        Forms\Components\Select::make('loan_officer_id')
                            ->label('Relationship Officer')
                            ->options(function () {
                                $tenantModel = Filament::getTenant();
                                return User::whereHas('branches', function (Builder $query) use ($tenantModel) {
                                    $query->whereHas('users', function (Builder $query) use ($tenantModel) {
                                        $query->where('branch_id', $tenantModel->id);
                                    });
                                })->pluck('name', 'id');
                            })
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('loan_purpose_id')
                            ->label('Loan Purpose')
                            ->options(LoanPurpose::all()->pluck('name', 'id'))
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->required(),
                            ])
                            ->createOptionUsing(function (array $data): int {
                                return LoanPurpose::create($data)->id;
                            })
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Hidden::make('loan_transaction_processing_strategy_id'),


                          ]) ->columns(2) ->hidden(fn (Get $get): bool => ! $get('loan_product_id')),
                        Forms\Components\Hidden::make('submitted_on_date')
                            ->default(Carbon::now()),

                       
                        Forms\Components\Hidden::make('instalment_multiple_of')
                            ->default(1),
                        Forms\Components\Hidden::make('interest_methodology'),
                        Forms\Components\Hidden::make('amortization_method'),
                        Forms\Components\Hidden::make('auto_disburse'),
                        Forms\Components\Hidden::make('status')
                            ->default('pending'),
                ]),
            ];
        }

    public static function getGuarantorsInformation(): array
    {
        return [
            Repeater::make('guarantors')
            ->addActionLabel('Add Guarantor')
            ->relationship('guarantors')
            ->minItems(1)
                ->schema([
                    Forms\Components\Hidden::make('client_id')
                    ->default(fn (Get $get): ?int => $get('../../client_id'))
                    ->required(),
                    Forms\Components\Select::make('is_previous')
                    ->label('Previous Guarantor?')
                    ->options([
                        '1' => 'Yes',
                        '0' => 'No',
                    ])
                    ->placeholder('Select')
                    ->live()
                    ->afterStateUpdated(fn (Select $component) => $component
                        ->getContainer()
                        ->getComponent('newGuarantor')
                        ->getChildComponentContainer()
                        ->fill()),

                Grid::make(2)
                        ->schema(fn (Forms\Get $get): array => match ($get('is_previous')) {
                            '1' => [
                                Select::make('client')
                                ->label('Client Name')
                                ->searchable()
                                ->placeholder('Select Client')
                                ->options(
                                    LoanGuarantor::where('client_id', $get('client_id'))
                                                ->get()
                                                ->pluck('fullname', 'client_id')
                                )
                                ->live()
                                ->reactive()
                                ->afterStateUpdated(function (Set $set, Get $get)  {
                                    $guarantorId = $get('client');
                                    
                                    $guarantor = LoanGuarantor::where('client_id', $guarantorId)->latest()->first();
                                    if($guarantor) {
                                        $set('title_id', $guarantor->title_id);
                                        $set('first_name', $guarantor->first_name);
                                        $set('middle_name', $guarantor->middle_name);
                                        $set('last_name', $guarantor->last_name);
                                        $set('email', $guarantor->email);
                                        $set('phone', $guarantor->phone);
                                        $set('mobile', $guarantor->mobile);
                                        $set('address', $guarantor->address);
                                        $set('city', $guarantor->city);
                                        $set('status', $guarantor->status);
                                        $set('state', $guarantor->state);
                                        $set('country_id', $guarantor->country_id);
                                        $set('zip', $guarantor->zip);
                                        $set('profession_id', $guarantor->profession_id);
                                        $set('client_relationship_id', $guarantor->client_relationship_id);
                                        $set('guaranteed_amount', $guarantor->guaranteed_amount);
                                        $set('photo', $guarantor->photo);
                                        $set('notes', $guarantor->notes);
                                        $set('marital_status', $guarantor->marital_status);
                                        $set('gender', $guarantor->gender);
                                        $set('id_number', $guarantor->id_number);



                                    }
                                })
                                ->required(),
                                TextInput::make('guaranteed_amount')
                                        ->numeric()
                                        ->required()
                                        ->prefix('KES'),
                                    Select::make('client_relationship_id')
                                        ->label('Relationship')
                                        ->placeholder('Select Relationship')
                                        ->options(
                                            ClientRelationship::all()->pluck('name', 'id')
                                        )
                                        ->required(),
                                    Hidden::make('created_by_id')
                                         ->default(Auth::id()),
                                    Hidden::make('first_name'),
                                    Hidden::make('middle_name'),
                                    Hidden::make('last_name'),
                                    Hidden::make('gender'),
                                    Hidden::make('status'),
                                    Hidden::make('marital_status'),
                                    Hidden::make('country_id'),
                                    Hidden::make('title_id'),
                                    Hidden::make('profession_id'),
                                    Hidden::make('client_relationship_id'),
                                    Hidden::make('mobile'),
                                    Hidden::make('phone'),
                                    Hidden::make('email'),
                                    Hidden::make('id_number'),
                                    Hidden::make('address'),
                                    Hidden::make('city'),
                                    Hidden::make('zip'),
                                    Hidden::make('photo'),
                                    Hidden::make('notes'),
                                    ],

                            '0' => [
                                Forms\Components\Select::make('is_client')
                                    ->label('Is Client')
                                    ->options([
                                        '1' => 'Yes',
                                        '0' => 'No',
                                    ])
                                    ->placeholder('Select')
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(fn (Select $component) => $component
                                        ->getContainer()
                                        ->getComponent('dynamicTypeFields')
                                        ->getChildComponentContainer()
                                        ->fill()),
                                        Grid::make(2)
                                        ->schema(fn (Get $get): array => match ($get('is_client')) {
                                            '1' => [
                                                Select::make('client')
                                                    ->label('Client Name')
                                                    ->searchable()
                                                    ->placeholder('Select Client')
                                                    ->options(
                                                        Client::where('status', 'active')
                                                            ->whereNotIn('id', function ($query) {
                                                                $query->select('client_id')
                                                                    ->from('loan_guarantors');
                                                            })
                                                            ->get()
                                                            ->pluck('fullname', 'id')
                                                    )
                                                    ->live()
                                                    ->reactive()
                                                    ->afterStateUpdated(function (Set $set, Get $get)  {
                                                        $guarantor = Client::find($get('client'));
                                                        if($guarantor) {
                                                            $set('title_id', $guarantor->title_id);
                                                            $set('first_name', $guarantor->first_name);
                                                            $set('middle_name', $guarantor->middle_name);
                                                            $set('last_name', $guarantor->last_name);
                                                            $set('email', $guarantor->email);
                                                            $set('phone', $guarantor->phone);
                                                            $set('mobile', $guarantor->mobile);
                                                            $set('address', $guarantor->address);
                                                            $set('city', $guarantor->city);
                                                            $set('status', $guarantor->status);
                                                            $set('state', $guarantor->state);
                                                            $set('country_id', $guarantor->country_id);
                                                            $set('zip', $guarantor->zip);
                                                            $set('profession_id', $guarantor->profession_id);
                                                            $set('client_relationship_id', $guarantor->client_relationship_id);
                                                            $set('guaranteed_amount', $guarantor->guaranteed_amount);
                                                            $set('photo', $guarantor->photo);
                                                            $set('notes', $guarantor->notes);
                                                            $set('marital_status', $guarantor->marital_status);
                                                            $set('gender', $guarantor->gender);
                                                            $set('id_number', $guarantor->account_number);



                                                        }
                                                    })
                                                    ->required(),
                                                TextInput::make('guaranteed_amount')
                                                    ->numeric()
                                                    ->required()
                                                    ->prefix('KES'),
                                                Select::make('client_relationship_id')
                                                    ->label('Relationship')
                                                    ->placeholder('Select Relationship')
                                                    ->options(
                                                        ClientRelationship::all()->pluck('name', 'id')
                                                    )
                                                    ->required(),
                                                 Hidden::make('created_by_id')
                                                     ->default(Auth::id()),
                                                Hidden::make('first_name'),
                                                Hidden::make('middle_name'),
                                                Hidden::make('last_name'),
                                                Hidden::make('gender'),
                                                Hidden::make('status'),
                                                Hidden::make('marital_status'),
                                                Hidden::make('country_id'),
                                                Hidden::make('title_id'),
                                                Hidden::make('profession_id'),
                                                Hidden::make('client_relationship_id'),
                                                Hidden::make('mobile'),
                                                Hidden::make('phone'),
                                                Hidden::make('email'),
                                                Hidden::make('id_number'),
                                                Hidden::make('address'),
                                                Hidden::make('city'),
                                                Hidden::make('zip'),
                                                Hidden::make('photo'),
                                                Hidden::make('notes'),
                                            ],
                                            '0' => [
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
                                                    ->required()
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
                                                    ->placeholder('Select Profession')
                                                    ->relationship('profession','name')
                                                    ->required()
                                                    ->preload()
                                                    ->searchable(),
                                                FilamentPhoneNumbers\Forms\Components\PhoneNumber::make('mobile')
                                                    ->label('Mobile')
                                                    ->region('KE')
                                                    ->displayFormat(PhoneNumberFormat::E164)
                                                    ->databaseFormat(PhoneNumberFormat::INTERNATIONAL)
                                                    ->mask('9999999999')
                                                    ->required(),
                                                TextInput::make('email')
                                                    ->email()
                                                    ->maxLength(255),
                                                TextInput::make('id_number')
                                                    ->numeric()
                                                    ->required()
                                                    //->unique(ignoreRecord: true)
                                                    ->maxLength(255),
                                                Textarea::make('address')
                                                    ->maxLength(65535),
                                                TextInput::make('city')
                                                    ->maxLength(255),
                                                TextInput::make('state')
                                                    ->maxLength(255),
                                                FileUpload::make('photo')
                                                    ,
                                                TextInput::make('guaranteed_amount')
                                                    ->numeric()
                                                    ->required()
                                                    ->prefix('KES'),
                                                Textarea::make('notes')
                                                    ->maxLength(65535)
                                                    ->columnSpanFull(),
                                            ],
                                            default => [],
                                        })
                                        ->key('dynamicTypeFields'),
                            ],
                            default => [],
                            })
                            ->key('newGuarantor'),
                        ]),
        ];
    }

    public static function getCollateralInformation(): array
    {
        return [
            Repeater::make('collaterals')
            ->addActionLabel('Add Collateral')
            ->itemLabel(fn (array $state): ?string => $state['description'] ?? null)
            ->columns(3)
            ->relationship('collateral')
                ->schema([
                    Forms\Components\Hidden::make('created_by_id')
                    ->default(Auth::id()),
                Forms\Components\Select::make('loan_collateral_type_id')
                    ->relationship('collateral_type', 'name'),
                Forms\Components\TextInput::make('value')
                    ->prefix('KES')
                    ->live()
                    ->afterStateUpdated(function (Set $set, Get $get)  {
                        //divide the value to half
                        $set('forced_value', $get('value') / 2);
                    })
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('forced_value')
                    ->prefix('KES')
                    ->readOnly(),
                Forms\Components\Textarea::make('description'),
                Forms\Components\FileUpload::make('file')
                    ->required(),
                // Forms\Components\Select::make('status')
                //     ->options(CollateralStatus::class)
                //     ->default('active'),
                ]),
        ];
    }
    public static function getFilesInformation(): array
    {
        return [
            Repeater::make('file')
            ->addActionLabel('Add File')
            ->relationship('files')
            ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
            ->columns(3)
                ->schema([
                Forms\Components\Hidden::make('created_by_id')
                    ->default(Auth::id()),
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->placeholder('Enter Title Name'),
                Forms\Components\TextInput::make('description'),
                Forms\Components\FileUpload::make('file')
                    ->required(),
                ]),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([
                ImportAction::make()
                ->importer(LoanImporter::class),
                ExportAction::make()
                ->exporter(LoanExporter::class),
            ])
            ->columns([
                Tables\Columns\TextColumn::make('loan_account_number')
                    ->label('Loan Account No')
                    ->sortable(),
                Tables\Columns\TextColumn::make('loan_officer.full_name')
                    ->label('Relationship Officer')
                    ->sortable(),
                Tables\Columns\TextColumn::make('client.full_name')
                    ->sortable()
                    ->searchable(['first_name', 'middle_name', 'last_name']),
                Tables\Columns\TextColumn::make('approved_amount')
                    ->label('Approved Amount')
                    ->money('KES')
                    ->sortable()
                    ->summarize(Sum::make()
                            ->label('Total Approved Amount')
                            ->money('KES'))
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('principal_disbursed_derived')
                    ->label('Principal Amount')
                    ->money('KES')
                    ->sortable()
                    ->summarize(Sum::make()
                            ->label('Total Disbursement')
                            ->money('KES')),
                Tables\Columns\TextColumn::make('interest_disbursed_derived')
                    ->label('Interest Amount')
                    ->money('KES')
                    ->sortable()
                    ->getStateUsing(fn (Loan $record) => $record->getInterestDisbursed($record->id))
                    ->summarize(Sum::make()
                            ->label('Total Interest Disbursed')
                            ->money('KES')),
                Tables\Columns\TextColumn::make('repayment_schedules.interest_repaid_derived')
                    ->label('Interest Paid')
                    ->money('KES')
                    ->sortable()
                    ->getStateUsing(fn (Loan $record) => $record->getInterestPaid($record->id))
                    ->summarize(Sum::make()
                            ->label('Total Interest')
                            ->money('KES')),
                Tables\Columns\TextColumn::make('repayment_schedules.total_due')
                    ->label('Balance')
                    ->getStateUsing(fn (Loan $record) => $record->getBalance($record->id))
                    ->money('KES')
                    ->summarize(Sum::make()
                            ->label('Total Balance')
                            ->money('KES'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('arrears')
                    ->label('Arrears')
                    ->getStateUsing(fn (Loan $record) => $record->getAmountDue($record->id))
                    ->money('KES')
                    ->summarize(Summarizer::make()
                                ->label('Total Arrears')
                                ->money('KES')
                                ->using(function ($query) {
                                    return $query->get()->sum(function ($record) {
                                        // Ensure we're working with a Loan model instance
                                        if ($record instanceof \stdClass) {
                                            $record = Loan::find($record->id);
                                        }
                                        return $record ? $record->getAmountDue($record->id) : 0;
                                    });
                                })
                            )
                    ->sortable(),
                Tables\Columns\TextColumn::make('arrears_days')
                    ->label(' Days In Arrears')
                    ->getStateUsing(fn (Loan $record) => $record->getDaysInArrears($record->id)),
                Tables\Columns\TextColumn::make('loan_product.name')
                    ->label('Loan Product')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('loan_product.name')
                    ->label('Loan Product')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('disbursed_on_date')
                    ->label('Disbursement Date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('client.account_number')
                    ->label('Account Number')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('client.mobile')
                    ->label('Phone Number')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])->striped()
            ->defaultSort('disbursed_on_date', 'desc')
            ->filters([
                SelectFilter::make('status')
                                ->label('Loan Status')
                                ->options(LoanStatus::class)
                                ->searchable(),
                SelectFilter::make('loan_officer_id')
                                ->label('Relationship Officer')
                                ->options(User::whereNotNull('first_name')->pluck(DB::raw("CONCAT(first_name, ' ', last_name)"), 'id'))
                                ->searchable(),
                DateRangeFilter::make('disbursed_on_date')
                                ->withIndicator()
                                    ,

            ], layout: FiltersLayout::AboveContent)

            ->filtersTriggerAction(
                fn (TableAction $action) => $action
                    ->button()
                    ->label('Filter'),
            )
            ->actions([

                Tables\Actions\ViewAction::make(),
            ActionGroup::make([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('approve')
                    ->label('Approve Loan')
                    ->icon('heroicon-s-check-circle')
                    ->modalHeading('Approve Loan')
                    ->visible(function(Loan $record) {
                        $policy = new LoanPolicy();
                        return $record->status->value === 'pending' && $policy->canBeApprovedBy(Auth::user(), $record);
                    })
                    ->fillForm(fn (Loan $record): array => [
                        'approved_amount' => $record->principal,
                        'approved_by_user_id' => Auth::id(),
                        'approved_on_date' => Carbon::now(),
                    ])
                    ->form([
                        TextInput::make('approved_amount')
                            ->label('Approved amount')
                            ->required(),
                        DatePicker::make('approved_on_date')
                            ->native(false),
                        Textarea::make('approved_notes')
                            ->label('Approved Notes'),
                    ])
                    ->action(function (Loan $record, array $data) {
                          //check loan limit
                       if($record->client->suggested_loan_limit >= $data['approved_amount']|| $record->client->suggested_loan_limit ==null  ){
                        // Disburse logic
                        $record->approveLoan($data);
                     //fire disbursement event

                     Notification::make()
                         ->title('Loan Approved Successfully')
                         ->success()
                         ->body('The Loan has been Approved ')
                         ->send();
                    } else {
                     Notification::make()
                         ->title('Loan Limit Exceeded')
                         ->danger()
                         ->body('The Loan Exceed Limit. Choose Lower Amount')
                         ->send();
                    }
                    })
                    ->color('success')
                    ->requiresConfirmation(),

                Tables\Actions\Action::make('Reject')
                    ->label('Reject Loan')
                    ->icon('heroicon-s-x-circle')
                    ->modalHeading('Reject Loan')
                    ->visible(function(Loan $record) {
                        $policy = new LoanPolicy();
                        return $record->status->value === 'pending' && $policy->canBeRejectedBy(Auth::user(), $record);
                    })
                    ->fillForm(fn (Loan $record): array => [
                        'applied_amount' => $record->principal,
                        'rejected_by_user_id' => Auth::id(),
                        'rejected_on_date' => Carbon::now(),
                    ])
                    ->form([
                        TextInput::make('applied_amount')
                            ->label('Applied amount')
                            ->disabled(true)
                            ->required(),
                        DatePicker::make('rejected_on_date')
                            ->native(false),
                        Textarea::make('rejected_notes')
                            ->label('Rejected Notes')
                            ->required(),
                    ])
                    ->action(function (Loan $record, array $data) {
                        $record->rejectLoan($data);
                    })
                    ->color('danger')
                    ->requiresConfirmation(),

                Tables\Actions\Action::make('undoReject')
                    ->label('Resubmit Loan')
                    ->icon('heroicon-s-arrow-uturn-right')
                    ->color('info')
                    ->modalHeading('Resubmit Loan')
                    ->visible(function(Loan $record) {
                        $policy = new LoanPolicy();
                        return $record->status->value === 'rejected' && $policy->canBeRejectedBy(Auth::user(), $record);
                    })
                    ->action(function (Loan $record) {
                        $record->undoLoanReject();
                    })
                    ->requiresConfirmation(),

                Tables\Actions\Action::make('unapprove')
                    ->label('Unapprove')
                    ->icon('heroicon-s-arrow-uturn-left')
                    ->color('danger')
                    ->modalHeading('Unapprove Loan')
                    ->visible(function(Loan $record) {
                        $policy = new LoanPolicy();
                        return $record->status->value === 'approved' && $policy->canBeApprovedBy(Auth::user(), $record);
                    })
                    ->action(function (Loan $record) {
                        $record->unapproveLoan();
                    })
                    ->color('danger')
                    ->requiresConfirmation(),
                Tables\Actions\Action::make('Disburse')
                    ->label('Disburse Loan')
                    ->icon('heroicon-s-arrow-uturn-right')
                    ->color('success')
                    ->modalHeading('Disburse Loan')
                    ->visible(function(Loan $record) {
                        $policy = new LoanPolicy();
                        return $record->status->value === 'approved' && $policy->canBeDisbursedBy(Auth::user(), $record);
                    })
                    ->fillForm(fn (Loan $record): array => [
                        'approved_amount' => $record->applied_amount,
                        'disbursed_by_user_id' => Auth::id(),
                        'disbursed_on_date' => Carbon::now(),
                        'first_payment_date' => $record->expected_first_payment_date,
                    ])
                    ->form([
                        Hidden::make('approved_amount')
                            ->label('Approved amount'),
                        DatePicker::make('disbursed_on_date')
                            ->label('Disbursed On Date')
                            ->native(false),
                        DatePicker::make('first_payment_date')
                            ->label('First Payment Date')
                            ->native(false),
                        Select::make('payment_type_id')
                            ->label('Payment Method')
                            ->options(PaymentType::all()->pluck('name', 'id')),
                        Textarea::make('disbursed_notes')
                            ->maxLength(100),
                    ])
                    ->action(function (Loan $record, array $data) {
                        $record->disburseLoan($data,$record);
                        event(new LoanDisbursed($record));
                           Notification::make()
                             ->title('Loan Disbursed Successfully')
                             ->success()
                             ->body('The Loan has been disbursed ')
                             ->send();
                      

                      })
                    ->requiresConfirmation(),


                Tables\Actions\Action::make('undisburse')
                    ->label('Undisburse')
                    ->icon('heroicon-s-arrow-uturn-left')
                    ->color('warning')
                    ->modalHeading('Undisburse Loan')
                    ->visible(function(Loan $record) {
                        $policy = new LoanPolicy();
                        return $record->status->value === 'active' && $policy->canBeDisbursedBy(Auth::user(), $record);
                    })
                    ->action(function (Loan $record) {
                        $record->undisburseLoan($record);
                        UndisburseLoanJob::dispatch($record);
                        Notification::make()
                            ->title('Loan Undisbursed Successfully')
                            ->success()
                            ->body('The Loan has been undisbursed')
                            ->send();
                    })
                    ->requiresConfirmation(),

                Tables\Actions\Action::make('close')
                    ->label('Close Loan')
                    ->icon('heroicon-s-x-circle')
                    ->color('danger')
                    ->modalHeading('Close Loan')
                    ->visible(function(Loan $record) {
                        $policy = new LoanPolicy();
                        return $record->status->value === 'active' && $policy->canBeClosedBy(Auth::user(), $record);
                    })
                    //->visible(fn(ApprovableModel $record)=> $record->isApprovalCompleted())
                    ->action(function (Loan $record) {
                        $record->closeLoan();
                    })
                    ->requiresConfirmation(),
               Tables\Actions\Action::make('reschedule')
                    ->label('Reschedule Loan')
                    ->icon('heroicon-o-arrow-path')
                    ->color('info')
                    ->modalHeading('Reschedule Loan')
                    ->visible(function(Loan $record) {
                        $policy = new LoanPolicy();
                        return $record->status->value === 'active' && $policy->canBeClosedBy(Auth::user(), $record);
                    })
                     ->form([
                        Textarea::make('rescheduled_notes')
                            ->maxLength(100),
                    ])
                    ->action(function (Loan $record, array $data) {
                        $record->rescheduleLoan($data);
                    })
                    ->requiresConfirmation(),
                Tables\Actions\Action::make('Activate')
                    ->label('Activate Loan')
                    ->icon('heroicon-s-check')
                    ->color('success')
                    ->modalHeading('Activate Loan')
                    ->visible(function(Loan $record) {
                        $policy = new LoanPolicy();
                        return $record->status->value === 'closed' && $policy->canBeClosedBy(Auth::user(), $record);
                    })

                    ->action(function (Loan $record) {
                        $record->activateLoan();
                    })
                    ->color('success')
                    ->requiresConfirmation(),
                ]),


            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    BulkAction::make('status')
                        ->label('Approve Loans')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Approve Loan')
                        ->visible(function(Loan $record) {
                            $policy = new LoanPolicy();
                            return $policy->canBeApprovedBy(Auth::user(), $record);
                        })
                        ->fillForm(fn (Loan $record): array => [
                            'approved_by_user_id' => Auth::id(),
                            'approved_on_date' => Carbon::now(),
                        ])
                        ->form([
                            DatePicker::make('approved_on_date')
                                ->native(false),
                            Textarea::make('approved_notes')
                                ->label('Approved Notes'),
                        ])
                        ->action(fn (Collection $records, array $data) => $records->each->BulkLoanApprove($data))
                        ->deselectRecordsAfterCompletion(),

                        BulkAction::make('unapprove')
                        ->label('Unapprove Loans')
                        ->icon('heroicon-o-arrow-uturn-left')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Unapprove Loan')
                        ->visible(function(Loan $record) {
                            $policy = new LoanPolicy();
                            return $policy->canBeApprovedBy(Auth::user(), $record);
                        })
                        ->action(fn (Collection $records, array $data) => $records->each->unapproveLoan())
                        ->deselectRecordsAfterCompletion(),


                    ExportBulkAction::make()
                     ->exporter(LoanExporter::class)
                     ->requiresConfirmation()
                     ->deselectRecordsAfterCompletion(),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])->selectCurrentPageOnly();

    }


    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                InfolistSection::make('Loan Details')
                ->headerActions([
                    InfolistAction::make('Change Loan Officer')

                            ->fillForm(fn (Loan $record): array => [
                                'loan_officer_id' => $record->loan_officer_id,
                            ])
                            ->form([
                                Select::make('loan_officer_id')
                                ->label('Loan Officer')
                                ->options(User::all()->pluck('fullname', 'id')->toArray()),
                            ])
                            ->visible(function(Loan $record) {
                                $policy = new LoanPolicy();
                                return $record->status->value === 'active' && $policy->canChangeLoanOfficer(Auth::user(), $record);
                            })
                            ->action(function (Loan $record, array $data) {
                                $officer = $record->changeLoanOfficer($data,$record);

                                Notification::make()
                                    ->success()
                                    ->title('Loan Officer Changed')
                                    ->body('The Loan Officer has been changed successfully.')
                                    ->send();
                            }),

                    InfolistAction::make('Repay Loan')
                        ->icon('heroicon-s-credit-card')
                        ->modalHeading('Repay Loan')
                        ->visible(function(Loan $record) {
                            $policy = new LoanPolicy();
                            return $record->status->value === 'active' && $policy->canBeRepayedBy(Auth::user(), $record);
                        })
                        ->fillForm(fn (Loan $record): array => [
                            'loan_id' => $record->id,
                            'account_number' => $record->client->account_number,
                            'created_by_id' => Auth::id(),
                            'created_on' => Carbon::now(),
                            'name' => 'Repayment',
                            'debit' => 0,
                        ])
                        ->form([
                            Forms\Components\TextInput::make('amount')
                            ->numeric()
                            ->inputMode('decimal')
                            ->afterStateUpdated(function (Set $set, $state) {
                                $set('credit', $state);})
                            ->required(),
                            Forms\Components\TextInput::make('reference')
                               ->label('Transaction Code')
                               ->required(),
                            Forms\Components\DatePicker::make('submitted_on')
                                ->label('Date of Transaction')
                                ->native(false)
                                ->required(),
                            Forms\Components\Hidden::make('credit'),
                            Forms\Components\TextInput::make('account_number')->required(),
                            Forms\Components\Hidden::make('loan_id'),
                            Forms\Components\Hidden::make('created_by_id'),
                            Forms\Components\Hidden::make('created_on'),
                            Forms\Components\Hidden::make('debit'),
                            Forms\Components\Hidden::make('name'),

                                ])
                        ->action(function (Loan $record, array $data) {

                            $referenceExists = LoanTransactions::where('reference', $data['reference'])->exists();
                            if (!$referenceExists) {
                            $transaction = $record->saveTransaction($data,$record);

                            event(new LoanRepayment($record,$data));

                            Notification::make()
                             ->success()
                             ->title('Transaction Created')
                             ->body('The Transaction has been created successfully.')
                             ->send();
                            } else
                            {
                                Notification::make()
                                ->danger()
                                ->title('Duplicate Transaction')
                                ->body('The transaction is a duplicate of previous transaction.')
                                ->send();
                            }
                        }),

                    InfolistAction::make('Add Charges')
                        ->icon('heroicon-m-plus')
                        ->visible(function(Loan $record) {
                            $policy = new LoanPolicy();
                            return $record->status->value === 'active' && $policy->canAddCharges(Auth::user(), $record);
                        })
                        ->fillForm(fn (Loan $record): array => [
                            'loan_id' => $record->id,
                        ])
                        ->form([
                            Select::make('loan_charge_id')
                                ->label('Charge Name')
                                ->options(LoanCharge::query()->pluck('name', 'id'))
                                ->live()
                                ->afterStateUpdated(function (Set $set, $state) {
                                    $set('name', LoanCharge::find($state)->name);
                                    $set('amount', LoanCharge::find($state)->amount);
                                    $set('loan_charge_type_id', LoanCharge::find($state)->loan_charge_type_id);
                                    $set('loan_charge_option_id', LoanCharge::find($state)->loan_charge_option_id);
                                    $set('is_penalty', LoanCharge::find($state)->is_penalty);

                                })
                                ->required(),
                            Forms\Components\TextInput::make('amount'),
                            Forms\Components\TextInput::make('name'),
                            Forms\Components\Toggle::make('is_penalty')->required(),
                            Forms\Components\DatePicker::make('date')
                                   ->native(false)
                                   ->default(Carbon::now()),
                            Forms\Components\Hidden::make('loan_charge_id'),
                            Forms\Components\Hidden::make('loan_charge_type_id'),
                            Forms\Components\Hidden::make('loan_charge_option_id'),
                            Forms\Components\Hidden::make('loan_id'),

                        ])
                        ->action(function (Loan $record, array $data) {

                            $charges = $record->saveLoanCharges($data,$record);



                            Notification::make()
                             ->success()
                             ->title('Charge Created')
                             ->body('The Loan Charge has been created successfully.')
                             ->send();
                        }),

                        InfolistAction::make('Initiate Payment')
                            ->modalHeading('Initiate Mpesa STK Payment')
                            ->modalDescription('This will initiate a payment request to the client.')
                            ->modalIcon('heroicon-o-credit-card')
                            ->form([
                                Forms\Components\TextInput::make('amount')
                                    ->required()
                                    ->numeric(),
                            ])
                            ->action(function (Loan $record, array $data) {
                                $mpesaController = app(\App\Http\Controllers\MpesaController::class);
                                $mpesaController->initiateStkRequest(new \Illuminate\Http\Request([
                                    'amount' => $data['amount'],
                                    'msisdn' => $record->client->mobile
                                ]));
                                
                                \Filament\Notifications\Notification::make()
                                    ->title('Payment request sent')
                                    ->body('An STK push has been sent to the client\'s phone')
                                    ->success()
                                    ->send();
                            })
                ])
                    ->schema([
                        Split::make([
                            Fieldset::make('Client Details')->schema([
                                TextEntry::make('client.full_name')
                                    ->label('Client Name')
                                    ->color('info'),
                                TextEntry::make('loan_officer.full_name')
                                    ->color('info'),
                                TextEntry::make('status')
                                    ->badge(),
                                TextEntry::make('principal')
                                    ->label('Applied Amount')
                                    ->money(Currency::where('is_default', 1)->first()->symbol)
                                    ->badge('info')
                                    ->color('info'),
                                TextEntry::make('approved_amount')
                                    ->money(Currency::where('is_default', 1)->first()->symbol)
                                    ->badge('info')
                                    ->color('info'),
                                TextEntry::make('principal_disbursed_derived')
                                     ->label('Disbursed Amount')
                                     ->money(Currency::where('is_default', 1)->first()->symbol)
                                     ->badge('info')
                                     ->color('info'),
                                TextEntry::make('disbursed_on_date')
                                    ->date()
                                    ->color('info'),
                                TextEntry::make('first_payment_date')
                                    ->date()
                                    ->color('info'),
                                TextEntry::make('Balance')
                                    ->label('Current Balance')
                                    ->getStateUsing(fn (Loan $record) => $record->getBalance($record->id))
                                    ->money('KES')
                                    ->badge('info')
                                    ->color('info'),
                                TextEntry::make('Amount Repaid')
                                    ->label('Amount Repaid')
                                    ->getStateUsing(fn (Loan $record) => $record->getAmountRepaid($record->id))
                                    ->money('KES')
                                    ->badge('success')
                                    ->color('success'),
                                TextEntry::make('Amount Due')
                                    ->label('Amount Due')
                                    ->getStateUsing(fn (Loan $record) => $record->getAmountDue($record->id))
                                    ->money('KES')
                                    ->badge('danger')
                                    ->color('danger'),
                                TextEntry::make('Charges')
                                    ->label('Charges')
                                    ->getStateUsing(fn (Loan $record) => $record->getCharges($record->id))
                                    ->money(Currency::where('is_default', 1)->first()->symbol)
                                    ->badge('warning')
                                    ->color('warning'),
                            ])->columns(2),
                            Fieldset::make('Account Details')->schema([
                                TextEntry::make('fund.name')
                                    ->label('Fund Acount')
                                    ->color('info'),
                                TextEntry::make('loan_transaction_processing_strategy.name')
                                    ->label('Loan processing strategy')
                                    ->color('info'),
                                TextEntry::make('loan_term')
                                    ->suffix(' Days')
                                    ->label('Loan Term ')
                                    ->color('info'),
                                TextEntry::make('repayment_frequency')
                                     ->label('Repayment')
                                     ->prefix('Every ')
                                     ->suffix(' Day')
                                    ->color('info'),
                                TextEntry::make('interest_methodology')
                                    ->color('info'),
                                TextEntry::make('interest_rate')
                                    ->label('Interest Rate')
                                    ->suffix(' % ')
                                    ->color('info'),
                                TextEntry::make('submitted_by.fullname')
                                    ->color('info'),
                                TextEntry::make('approved_by.fullname')
                                    ->color('info'),
                                TextEntry::make('disbursed_by.fullname')
                                    ->color('info'),
                            ])->columns(2),
                    ]),
                ]),


            ])
           ;

    }

    public static function getRelations(): array
    {
        return [
            RepaymentSchedulesRelationManager::class,
            TransactionsRelationManager::class,
            ChargesRelationManager::class,
            GuarantorsRelationManager::class,
            FilesRelationManager::class,
            CollateralRelationManager::class,
            NotesRelationManager::class,

        ];
    }


    public static function getPermissionPrefixes(): array
    {
        return [
            'view',
            'view_any',
            'create',
            'update',
            'delete',
            'delete_any',
            'approve',
            'reject',
            'disburse',
            'close',
            'repay',
            'add_charge',
            'change_loan_officer',
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLoans::route('/'),
            'create' => Pages\CreateLoan::route('/create'),
            'edit' => Pages\EditLoan::route('/{record}/edit'),
            'view' => Pages\ViewLoan::route('/{record}'),
        ];
    }

    public static function getGlobalSearchResultTitle(Model $record): string | Htmlable
    {
        return $record->client->first_name . ' ' . $record->client->middle_name . ' ' . $record->client->last_name;
    }

    public static function getGlobalSearchResultUrl(Model $record): string
    {
        return LoanResource::getUrl('view', ['record' => $record]);
    }

    public static function getGlobalSearchResultActions(Model $record): array
    {
        return [
            globalSearchAction::make('edit')
            ->icon('heroicon-m-pencil-square')
            ->iconButton()
                ->url(static::getUrl('edit', ['record' => $record])),

            globalSearchAction::make('view')
                ->icon('heroicon-m-eye')
                ->iconButton()
                ->url(static::getUrl('view', ['record' => $record])),
        ];
    }
    public static function getGloballySearchableAttributes(): array
    {
        return ['client.first_name', 'client.middle_name', 'client.last_name',  'client.account_number', 'client.mobile'];
    }
}
