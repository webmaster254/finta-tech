<?php

namespace App\Filament\Resources;

use Carbon\Carbon;
use Filament\Forms;
use App\Models\Town;
use App\Models\User;
use Filament\Tables;
use App\Enums\Gender;
use App\Enums\IDType;
use App\Enums\Status;
use App\Models\Title;
use App\Models\Client;
use App\Models\County;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Infolists;
use Filament\Forms\Form;
use App\Enums\LoanStatus;
use App\Enums\TypeOfTech;
use App\Models\SubCounty;
use App\Models\ClientType;
use App\Models\Profession;
use Filament\Tables\Table;
use App\Enums\Relationship;
use Carbon\CarbonInterface;
use App\Enums\MaritalStatus;
use App\Enums\EducationLevel;
use App\Enums\SourceOfIncome;
use App\Policies\ClientPolicy;
use Filament\Facades\Filament;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Awcodes\Curator\Models\Media;
use Awcodes\TableRepeater\Header;
use App\Models\ClientRelationship;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Filament\Forms\Components\Card;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Support\Facades\Auth;
use Cheesegrits\FilamentPhoneNumbers;
use Filament\Forms\Components\Wizard;
use Filament\Support\Enums\FontWeight;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Tabs;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Exports\ClientExporter;
use App\Filament\Imports\ClientImporter;
use Brick\PhoneNumber\PhoneNumberFormat;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\Split;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Pages\SubNavigationPosition;
use Filament\Tables\Actions\ExportAction;
use Filament\Tables\Actions\ImportAction;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\ClientResource;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Illuminate\Database\Eloquent\Collection;
use Filament\Tables\Actions\ExportBulkAction;
use Cheesegrits\FilamentGoogleMaps\Fields\Map;
use App\Filament\Resources\ClientResource\Pages;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;
use Filament\Infolists\Components\Actions\Action;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Tables\Actions\Action as TableAction;
use Ysfkaya\FilamentPhoneInput\Tables\PhoneColumn;
use Awcodes\Curator\Components\Forms\CuratorPicker;
use Awcodes\TableRepeater\Components\TableRepeater;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Awcodes\Curator\Components\Tables\CuratorColumn;
use Ysfkaya\FilamentPhoneInput\Infolists\PhoneEntry;
use Ysfkaya\FilamentPhoneInput\PhoneInputNumberType;
use Cheesegrits\FilamentGoogleMaps\Fields\Geocomplete;
use Saade\FilamentAutograph\Forms\Components\SignaturePad;
use App\Filament\Resources\ClientResource\RelationManagers;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Filament\GlobalSearch\Actions\Action as globalSearchAction;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Parfaitementweb\FilamentCountryField\Forms\Components\Country;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;
use Joaopaulolndev\FilamentPdfViewer\Forms\Components\PdfViewerField;
use Saade\FilamentAutograph\Forms\Components\Enums\DownloadableFormat;
use App\Filament\Resources\ClientResource\RelationManagers\SmsRelationManager;
use App\Filament\Resources\UserResource\RelationManagers\UsersRelationManager;
use App\Filament\Resources\ClientResource\RelationManagers\FilesRelationManager;
use App\Filament\Resources\ClientResource\RelationManagers\LoansRelationManager;
use App\Filament\Resources\ClientResource\RelationManagers\SpousesRelationManager;
use App\Filament\Resources\ClientResource\RelationManagers\RefereesRelationManager;
use App\Filament\Resources\ClientResource\RelationManagers\AddressesRelationManager;
use App\Filament\Resources\ClientResource\RelationManagers\NextOfKinsRelationManager;
use App\Filament\Resources\ClientResource\RelationManagers\ProfessionRelationManager;
use App\Filament\Resources\ClientResource\RelationManagers\RepaymentAccountRelationManager;


class ClientResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = Client::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'Customer 360 view';
    protected static ?int $navigationSort = 0;
    protected static ?string $recordTitleAttribute = 'fullname';
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;


    public static function getNavigationBadge(): ?string
{
    return static::getModel()::where('status', 'pending')->count();
}

public static function form(Form $form): Form
{
    return $form
        ->schema([
            Wizard::make()
            ->columnSpanFull()
                ->schema([
                    Wizard\Step::make('Personal Information')
                         ->description('Enter client personal information')
                         ->columns(2)
                        ->schema(self::getPersonalInformation()),
                    Wizard\Step::make('Address Information')
                        ->description('Enter client address information')
                        ->schema(self::getAddressInformation()),
                    Wizard\Step::make('Next of Kin Information')
                        ->description('Enter client next of kin information')
                        ->schema(self::getNextOfKinInformation()),
                    Wizard\Step::make('Spouse Information')
                    ->visible(fn (Forms\Get $get) => $get('marital_status') == 'married')
                        ->description('Enter client spouse information')
                        ->schema(self::getSpouseInformation()),
                    Wizard\Step::make('Referees Information')
                        ->description('Enter client referees information')
                        ->schema(self::getRefereesInformation()),
                    Wizard\Step::make('Client Lead')
                        ->description('Enter client lead information')
                        ->schema(self::getClientLead()),
                    Wizard\Step::make('Privacy Policy')
                        ->description('Enter client privacy policy information')
                        ->schema(self::getPrivacyPolicyInformation()),
                    Wizard\Step::make('Admin Information')
                        ->description('Enter client admin information')
                        ->schema(self::getAdminInformation()),
                ])
        ]);
}


    public static function table(Table $table): Table
    {
        return $table
        ->headerActions([
            ImportAction::make()
                ->importer(ClientImporter::class),
                ExportAction::make()
                ->exporter(ClientExporter::class),
        ])
            ->columns([
                Tables\Columns\ImageColumn::make('photo')
                    ->circular()
                    ->default(fn (Client $record) => 'https://ui-avatars.com/api/?background=random&name=' . urlencode($record->fullname)),
                Tables\Columns\TextColumn::make('fullname')
                    ->label('Full Name')
                    ->searchable(['first_name', 'middle_name', 'last_name']),
                Tables\Columns\TextColumn::make('mobile')
                    ->searchable(),
                Tables\Columns\TextColumn::make('loan_officer.fullname')
                    ->label('Relationship Officer')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('suggested_loan_limit')
                   ->label('Loan Limit')
                   ->money('KES')
                   ->badge('info'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                                ->label('Client Status')
                                ->options(Status::class)
                                ->searchable(),
                SelectFilter::make('loan_officer_id')
                                ->label('Relationship Officer')
                                ->options(User::pluck(DB::raw("CONCAT(first_name, ' ', last_name)"), 'id'))
                                ->searchable(),
                DateRangeFilter::make('created_at')
                                ->label('Joined Date')
                                ->withIndicator(),


            ], layout: FiltersLayout::AboveContent)


            ->actions([
                ActionGroup::make([
                    Tables\Actions\Action::make('status')
                    ->label('Disapprove Client')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->modalHeading('Disapprove Client')
                    ->visible(function(Client $record) {
                        $policy = new ClientPolicy();
                        return $policy->update(Auth::user(), $record);
                    })
                    ->action(function (Client $record) {
                        $record->changeStatus('pending');
                        Notification::make()
                                    ->success()
                                    ->title('Client Disapproved')
                                    ->body('The client has been disapproved successfully.')
                                    ->send();

                    })
                    ->color('danger')
                    ->requiresConfirmation(),
                    Tables\Actions\Action::make('officer')
                        ->label('Change Loan Officer')
                        ->color('info')
                        ->icon('heroicon-o-arrow-path')
                        ->modalHeading('Change Loan Officer')
                        ->visible(function(Client $record) {
                            $policy = new ClientPolicy();
                            return $policy->update(Auth::user(), $record);
                        })
                        ->form([
                            Forms\Components\Select::make('loan_officer_id')
                                ->label('Loan Officer')
                                ->preload(true)
                                ->options(User::pluck(DB::raw("CONCAT(first_name, ' ', last_name)"), 'id'))
                                ->required(),
                        ])
                        ->action(function (Client $record,array $data) {
                            $record->changeLoanOfficer($data['loan_officer_id']);
                            Notification::make()
                                        ->success()
                                        ->title('Loan Officer Changed')
                                        ->body('The loan officer has been changed successfully.')
                                        ->send();
                        }),
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),

                ])

            ])
            ->bulkActions([

                Tables\Actions\BulkActionGroup::make([
                    BulkAction::make('status')
                    ->label('Change Status')
                    ->icon('heroicon-o-arrow-path')
                    ->color('success')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Select::make('status')
                            ->options(Status::class)
                            ->required(),
                    ])
                    ->visible(function(Client $record) {
                        $policy = new ClientPolicy();
                        return $policy->update(Auth::user(), $record);
                    })
                    ->action(fn (Collection $records, array $data) => $records->each->changeStatus($data['status']))
                    ->deselectRecordsAfterCompletion(),

                    BulkAction::make('loan_officer')
                    ->label('Change Loan Officer')
                    ->icon('heroicon-o-arrow-path')
                    ->color('info')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Select::make('loan_officer_id')
                            ->options(User::pluck(DB::raw("CONCAT(first_name, ' ', last_name)"), 'id'))
                            ->required(),
                    ])
                    ->visible(function(Client $record) {
                        $policy = new ClientPolicy();
                        return $policy->update(Auth::user(), $record);
                    })
                    ->action(fn (Collection $records, array $data) => $records->each->changeLoanOfficer($data['loan_officer_id']))
                    ->deselectRecordsAfterCompletion(),

                    ExportBulkAction::make()
                     ->exporter(ClientExporter::class)
                     ->requiresConfirmation()
                     ->deselectRecordsAfterCompletion(),
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(function(Client $record) {
                            $policy = new ClientPolicy();
                            return $policy->delete(Auth::user(), $record);
                        }),
                ]),
            ])->selectCurrentPageOnly();
    }



    public static function infolist(Infolist $infolist): Infolist
    {   
        return $infolist
             ->schema([
                Split::make([
                    Section::make('Personal Information')

                    ->schema([
                        Infolists\Components\ImageEntry::make('photo')
                            ->height(200)
                            ->width(200)
                            ->default(fn (Client $record) => 'https://ui-avatars.com/api/?background=random&name=' . urlencode($record->fullname)),
                        Infolists\Components\TextEntry::make('fullname')
                            ->color('info')
                            ->label('Full Name')
                            ->columnSpan(2),
                        FilamentPhoneNumbers\Infolists\Components\PhoneNumberEntry ::make('mobile')
                            ->color('info')
                            ->displayFormat(PhoneNumberFormat::INTERNATIONAL),
                        FilamentPhoneNumbers\Infolists\Components\PhoneNumberEntry ::make('other_mobile_no')
                            ->color('info')
                            ->displayFormat(PhoneNumberFormat::INTERNATIONAL),
                        Infolists\Components\TextEntry::make('id_number')
                            ->color('info')
                            ->label('ID Number'),
                        Infolists\Components\TextEntry::make('account_number')
                            ->color('info')
                            ->label('Account Number'),
                        Infolists\Components\TextEntry::make('loan_officer.fullname')
                            ->color('info')
                            ->label('Loan Officer'),
                      Infolists\Components\TextEntry::make('suggested_loan_limit')
                            ->label('Loan Limit')
                            ->money('KES')
                            ->color('success')
                            ->badge(),
                      Infolists\Components\TextEntry::make('score')
                            ->color('success')
                            ->badge()
                            ->label('Credit Score')
                            ->suffix(' %')
                            ->getStateUsing(function($record) {
                                return $record->calculatePaymentHabitScore();
                            }),
                       Infolists\Components\TextEntry::make('loan')
                            ->color('success')
                            ->badge()
                            ->label('Active Loans')
                            ->getStateUsing(function($record) {
                                return $record->loans()->where('status', 'active')->count();
                            }),
                        Infolists\Components\TextEntry::make('loan')
                            ->color('success')
                            ->badge()
                            ->label('Closed Loans')
                            ->getStateUsing(function($record) {
                                return $record->loans()->where('status', 'closed')->count();
                            }),
                        Infolists\Components\ImageEntry::make('signature')
                            ->label('Signature'),
                    ])->columns(3),
                ]),
                Split::make([

                    Section::make('More Information')

                    ->headerActions([
                          Action::make('Initiate Payment')
                                ->modalHeading('Initiate Mpesa STK Payment')
                                ->modalDescription('This will initiate a payment request to the client.')
                                ->modalIcon('heroicon-o-credit-card')
                        ->form([
                            Forms\Components\TextInput::make('amount')
                                ->required()
                                ->numeric(),
                        ])
                       ->action(function (Client $record, array $data) {
                            
                            $mpesaController = app(\App\Http\Controllers\MpesaController::class);
                            $mpesaController->initiateStkRequest(new \Illuminate\Http\Request([
                                'amount' => $data['amount'],
                                'msisdn' => $record->mobile
                            ]));
                            
                            \Filament\Notifications\Notification::make()
                                ->title('Payment request sent')
                                ->body('An STK push has been sent to the client\'s phone')
                                ->success()
                                ->send();
                        })

                        

                    
                    ])

                    ->schema([
                        Infolists\Components\TextEntry::make('status')
                            //->color('info')
                            ->badge(),
                       Infolists\Components\ImageEntry::make('id_front'),
                        Infolists\Components\ImageEntry::make('id_back'),
                        Infolists\Components\TextEntry::make('email')
                            ->color('info')
                            ->columnSpan(2),
                        Infolists\Components\TextEntry::make('education_level')
                            ->color('info'),
                        Infolists\Components\TextEntry::make('type_of_tech')
                            ->color('info')
                            ->label('Type of Technology'),
                        Infolists\Components\TextEntry::make('source_of_income')
                            ->color('info')
                            ->label('Source of Income'),
                        Infolists\Components\TextEntry::make('dob')
                            ->color('info')
                            
                            ->label('Date of Birth'),
                            Infolists\Components\TextEntry::make('dob')
                            ->color('info')
                            ->formatStateUsing(function ($state): string {
                                if (is_string($state)) {
                                    $date = \Carbon\Carbon::parse($state);
                                } else {
                                    $date = $state;
                                }
                                return $date->age . ' years';
                            })
                            ->label('Age'),
                        Infolists\Components\TextEntry::make('notes')
                            ->color('info'),
                        Infolists\Components\TextEntry::make('created_at')
                            ->color('info')
                            ->label('Joined Date'),
                    ])->columns(3),

                ]),

           

            ]);


    }

    public static function getPersonalInformation(): array
    {
        return [
            Card::make()->schema([
                Forms\Components\Hidden::make('suggested_loan_limit')
                    ->default(10000),
                Forms\Components\Hidden::make('account_number'),
                Forms\Components\Select::make('client_type_id')
                    ->label('Client Type')
                    ->options(ClientType::all()->pluck('name', 'id'))
                    ->required(),
                Forms\Components\Select::make('title_id')
                    ->relationship('title','name'),
                Forms\Components\TextInput::make('first_name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('middle_name')
                    ->maxLength(255),
                Forms\Components\TextInput::make('last_name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('aka')
                    ->label('AKA')
                    ->maxLength(50),
                Forms\Components\Select::make('id_type')
                    ->label('ID Type')
                    ->required()
                    ->options(IDType::class),
                Forms\Components\TextInput::make('id_number')
                    ->label('ID Number')
                    ->numeric()
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->minLength(6)
                    ->maxLength(8),
                Forms\Components\select::make('gender')
                    ->required()
                    ->options(Gender::class),
                Forms\Components\Hidden::make('status')
                    ->default('pending'),
                Forms\Components\Select::make('marital_status')
                    ->options(MaritalStatus::class),
                Forms\Components\select::make('education_level')
                    ->label('Education Level')
                    ->required()
                    ->options(EducationLevel::class),
                Forms\Components\select::make('profession_id')
                    ->label('Profession')
                    ->required()
                    ->options(Profession::all()->pluck('name', 'id')),

                FilamentPhoneNumbers\Forms\Components\PhoneNumber::make('mobile')
                    ->label('Mobile')
                    ->region('KE')
                    ->displayFormat(PhoneNumberFormat::E164)
                    ->mask('9999999999')
                    ->required()
                    ->unique(ignoreRecord: true),
                FilamentPhoneNumbers\Forms\Components\PhoneNumber::make('other_mobile_no')
                    ->label('Other Mobile')
                    ->region('KE')
                    ->displayFormat(PhoneNumberFormat::E164)
                    ->mask('9999999999'),
                Forms\Components\TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->maxLength(50),
                Forms\Components\TextInput::make('kra_pin')
                    ->label('KRA PIN')
                    ->maxLength(25),
                Forms\Components\DatePicker::make('dob')
                    ->label('Date of Birth')
                    ->required(),
                Forms\Components\Select::make('source_of_income')
                    ->options(SourceOfIncome::class)
                    ->required(),
                Forms\Components\Select::make('type_of_tech')
                    ->required()
                    ->label('Type of Technology')
                    ->options(TypeOfTech::class),
                FileUpload::make('photo')
                    ->label('Avatar')
                    ->image()
                    ->imageEditor()
                   ->loadingIndicatorPosition('left')
                   ->panelAspectRatio('2:1')
                   ->panelLayout('integrated')
                   ->removeUploadedFileButtonPosition('right')
                   ->uploadButtonPosition('left')
                   ->uploadProgressIndicatorPosition('left'),
                FileUpload::make('id_front')
                     ->image()
                     ->imageEditor()
                    ->loadingIndicatorPosition('left')
                    ->panelAspectRatio('2:1')
                    ->panelLayout('integrated')
                    ->removeUploadedFileButtonPosition('right')
                    ->uploadButtonPosition('left')
                    ->uploadProgressIndicatorPosition('left')
                    ->required(),
                FileUpload::make('id_back')
                    ->label('ID Back')
                    ->image()
                    ->imageEditor()
                    ->loadingIndicatorPosition('left')
                    ->panelAspectRatio('2:1')
                    ->panelLayout('integrated')
                    ->removeUploadedFileButtonPosition('right')
                    ->uploadButtonPosition('left')
                    ->uploadProgressIndicatorPosition('left')
                    ->required(),
                FileUpload::make('signature')
                    ->label('Signature')
                    ->image()
                    ->imageEditor()
                    ->loadingIndicatorPosition('left')
                    ->panelAspectRatio('2:1')
                    ->panelLayout('integrated')
                    ->removeUploadedFileButtonPosition('right')
                    ->uploadButtonPosition('left')
                    ->uploadProgressIndicatorPosition('left')
                    ->required(),
                   Forms\Components\Textarea::make('notes')
                    ->label('Notes')
                    ->autosize(),
                    ])->columns(3),
                ];
    }

    public static function getAddressInformation(): array
    {
        return [
            Repeater::make('addresses')
            ->relationship('addresses')
            ->columns(3)
            ->addActionLabel('Add Address')
            ->label('Addresses')
            ->collapsible()
            ->defaultItems(2)
            ->minItems(2)
            ->maxItems(5)
            ->itemLabel(fn (array $state): ?string => $state['address_type'] ?? null)
            ->schema([
            Forms\Components\Select::make('address_type')
                        ->label('Address Type')
                        ->options([
                            'residential' => 'Residential',
                            'business' => 'Business',
                ])
                ->required()
                ->nullable(),
            Country::make('country')
                ->label('Country')
                ->required(),
           Forms\Components\Select::make('county_id')
                ->label('County')
                ->options(County::all()->pluck('name', 'id'))
                ->live()
                ->required(),
            Forms\Components\Select::make('sub_county_id')
                ->label('Sub County')
                ->live()
                ->options(fn (Get $get) => SubCounty::query()
                        ->where('county_id', $get('county_id'))
                        ->get()
                        ->pluck('name', 'id'))
                ->required(),
            Forms\Components\Select::make('ward_id')
               ->options(fn (Get $get) => Town::query()
                        ->where('sc_id', $get('sub_county_id'))
                        ->get()
                        ->pluck('name', 'id'))
                ->label('Ward')
                ->required(),
            Forms\Components\TextInput::make('village')
                ->maxLength(100),
            Forms\Components\TextInput::make('street')
                ->maxLength(100)
                ->required(),
            Forms\Components\TextInput::make('landmark')
                ->maxLength(255)
                ->required(),
            Forms\Components\TextInput::make('building')
                ->maxLength(100)
                ->required()    ,
            Forms\Components\TextInput::make('floor_no')
                ->maxLength(20),
            Forms\Components\TextInput::make('house_no')
                ->maxLength(20),
            Forms\Components\TextInput::make('estate')
                ->maxLength(100),
            Geocomplete::make('full_address'),
            Map::make('location')
                 ->reactive()
                ->label('Location')
                ->geolocate()
                ->afterStateUpdated(function ($state, callable $get, callable $set) {
                    $set('latitude', $state['lat']);
                    $set('longitude', $state['lng']);
                })
                ->required(),
            Forms\Components\TextInput::make('latitude')
            ->readOnly(),
            Forms\Components\TextInput::make('longitude')
            ->readOnly(),
            FileUpload::make('image')
                ->label('Image')
                ->image()
                ->imageEditor()
                ->imagePreviewHeight('250')
                ->loadingIndicatorPosition('left')
                ->panelAspectRatio('2:1')
                ->panelLayout('integrated')
                ->removeUploadedFileButtonPosition('right')
                ->uploadButtonPosition('left')
                ->uploadProgressIndicatorPosition('left')
                ->required(), 
            Forms\Components\Textarea::make('image_description')
                ->maxLength(20),    
                 ]),
        ];
    }

    public static function getNextOfKinInformation(): array
    {
        return [
            Repeater::make('next_of_kins')
            ->relationship('next_of_kins')
            ->label('Next of Kin')
            ->addActionLabel('Add Next of Kin')
            ->columns(3)
                ->schema([
                    Forms\Components\Hidden::make('created_by_id')
                    ->default(Auth::user()->id),
                    Forms\Components\TextInput::make('first_name')
                    ->label('First Name')
                    ->required()
                    ->maxLength(25),
                    Forms\Components\TextInput::make('middle_name')
                    ->label('Middle Name')
                    ->maxLength(25),
                    Forms\Components\TextInput::make('last_name')
                    ->label('Last Name')
                    ->required()
                    ->maxLength(25),
                FilamentPhoneNumbers\Forms\Components\PhoneNumber::make('mobile')
                    ->label('Mobile')
                    ->region('KE')  
                    ->displayFormat(PhoneNumberFormat::E164)
                    ->mask('9999999999')
                    ->required()
                    ->maxLength(20),
                    Forms\Components\Select::make('client_relationship_id')
                    ->label('Relationship')
                   ->options(ClientRelationship::all()->pluck('name', 'id'))
                    ->required(),
                    Forms\Components\TextInput::make('address')
                    ->maxLength(255)
                    ->required(),
                ])
           
        ];
    }

    public static function getSpouseInformation(): array
    {
        return [
            Card::make()
            ->relationship('spouse')
            ->schema([  
                Forms\Components\TextInput::make('name')
                ->label('Spouse Name')
                ->required()
                ->maxLength(255),
                Forms\Components\TextInput::make('id_number')
                ->label('Spouse ID Number')
                ->live()
                ->required()
                ->minLength(6)
                ->maxLength(8),
            FilamentPhoneNumbers\Forms\Components\PhoneNumber::make('mobile')
                ->label('Spouse Mobile')
                ->region('KE')
                ->displayFormat(PhoneNumberFormat::E164)
                ->mask('9999999999')
                ->required()
                ->maxLength(20),
            Forms\Components\TextInput::make('occupation')
                ->label('Spouse Occupation')
                ->maxLength(100),
            FileUpload::make('id_front')
                    ->label('ID Front')
                    ->image()
                    ->imageEditor()
                    ->imagePreviewHeight('250')
                    ->loadingIndicatorPosition('left')
                    ->panelAspectRatio('2:1')
                    ->panelLayout('integrated')
                    ->removeUploadedFileButtonPosition('right')
                    ->uploadButtonPosition('left')
                    ->uploadProgressIndicatorPosition('left')
                    ->required(),
            FileUpload::make('id_back')
                    ->label('ID Back')
                    ->image()
                    ->imageEditor()
                    ->imagePreviewHeight('250')
                    ->loadingIndicatorPosition('left')
                    ->panelAspectRatio('2:1')
                    ->panelLayout('integrated')
                    ->removeUploadedFileButtonPosition('right')
                    ->uploadButtonPosition('left')
                    ->uploadProgressIndicatorPosition('left')
                    ->required(),
                Forms\Components\FileUpload::make('photo')
                ->label('Photo')
                ->image()
                ->imageEditor()
                ->imagePreviewHeight('250')
                ->loadingIndicatorPosition('left')
                ->panelAspectRatio('2:1')
                ->panelLayout('integrated')
                ->removeUploadedFileButtonPosition('right')
                ->uploadButtonPosition('left')
                ->uploadProgressIndicatorPosition('left')
                ->required(),
            Forms\Components\Placeholder::make('consent_declaration')
                ->label('STATUTORY DECLARATION BY SPOUSE/LIVE IN COMPANION')
                ->columnSpanFull()
                ->content(''),
            Forms\Components\Placeholder::make('consent_notes')
                ->label(fn(Get $get) => new HtmlString('<p>1. That I am the holder of National Identity Card No <a class="underline" style="color:#0000FF">' . $get('id_number') . '</a></p>
                <p>2. That being the spouse/ live in companion of the Borrower hereby acknowledge and declare that I
                have full knowledge of this borrowing.</p>
                <p>3. That I understand the nature and effect of the borrowing, neither the Borrower nor the Lender have
                used any compulsion or threat or exercised undue influence on me to induce me to execute this 
                consent.</p>
                <p>4. That I acknowledge that I have been advised to take and have taken independent legal advice
                regarding the nature of this commercial transaction.</p>
                <p>5. That I HEREBY CONSENT TO THE SAME on the terms herein appearing and the creation of
                applicable security.</p>
                <p style="margin-bottom: 15px;">6. That I make this solemn declaration, conscientiously believing the same to be true and in accordance
                with the Oaths and Statutory Declarations Act.</p>
                <p style="margin-bottom: 15px; color:#0000FF">DECLARED on ' . Carbon::now()->toFormattedDateString() . '</p>'))
                ->columnSpanFull()
                ->content(''), 
           
            SignaturePad::make('consent_signature')
               ->label('Consent Signature')
                ->dotSize(2.0)
               ->lineMinWidth(0.5)
               ->backgroundColor('rgba(0,0,0,0)')  // Background color on light mode
               ->backgroundColorOnDark('#f0a')
               ->penColor('#0000FF')
               ->penColorOnDark('#fff') 
               ->lineMaxWidth(2.5)
               ->throttle(16)
               ->minDistance(5)
               ->required()
               ->velocityFilterWeight(0.7) 
                ->downloadable()                    // Allow download of the signature (defaults to false)
               ->downloadableFormats([             // Available formats for download (defaults to all)
                   DownloadableFormat::PNG,
                   DownloadableFormat::JPG,
                   DownloadableFormat::SVG,
               ]),
            ])
            ->columns(3),
        ];
    }

    public static function getRefereesInformation(): array
    {
        return [
            Repeater::make('referees')
            ->addActionLabel('Add Referees')
             ->relationship('referees')
                ->schema([
                        Forms\Components\TextInput::make('name')
                        ->label('Referee Name')
                        ->maxLength(255),
                    FilamentPhoneNumbers\Forms\Components\PhoneNumber::make('mobile')
                        ->label('Referee Mobile')
                        ->region('KE')
                        ->displayFormat(PhoneNumberFormat::E164)
                        ->mask('9999999999')
                        ->required(),
                    Forms\Components\Select::make('relationship')
                        ->label('Relationship')
                        ->options(Relationship::class)
                        ->required(),
                ])
                ->columns(3)
                ->minItems(3)
                ->maxItems(5),
        ];
    }

    public static function getClientLead(): array
    {
        return [
            Repeater::make('client_lead')
            ->relationship('client_lead')
            ->addActionLabel('Add Client Lead')
            ->columns(3)
            ->schema([
                Forms\Components\Select::make('lead_source')
                ->label('Source')
                ->live()
                ->options([
                    'field_staff' => 'Field Staff',
                    'posters' => 'Posters',
                    'walk_ins' => 'Walk Ins',
                    'existing_client' => 'Existing Client',
                    'other' => 'Other',
                ])
                ->required(),
                 Forms\Components\Select::make('existing_client')
                    ->label('Existing Client')
                    ->live()
                    ->visible(fn (Get $get): bool => $get('lead_source') === 'existing_client')
                    ->options(Client::where('status', 'active')->get()->mapWithKeys(function ($client) {
                        
                        return [$client->id => $client->first_name . ' ' . $client->last_name];
                    }))
                    ->required(),
               Forms\Components\Placeholder::make('client_account')
                ->label('Client Account')
                ->visible(fn (Get $get): bool => $get('lead_source') === 'existing_client')
                ->content(function (Forms\Get $get): ?string {
                    $client =Client::where('id', $get('existing_client'))->first();
                    return $client? $client->account_number:null;
                }),
            Forms\Components\Placeholder::make('client_status')
                ->label('Client Status')
                
                ->visible(fn (Get $get): bool => $get('lead_source') === 'existing_client')
                ->content(function (Forms\Get $get) {
                    $client = Client::where('id', $get('existing_client'))->first();
                    if (!$client) return null;
                    
                    return new \Illuminate\Support\HtmlString(
                        view('filament.components.status-badge', [
                            'status' => $client->status,
                        ])->render()
                    );
                }),
                Forms\Components\Textarea::make('others')
                    ->label('Others')
                    ->visible(fn (Get $get): bool => $get('lead_source') === 'other')
                    ->required(),

            ])
            ->columns(3),
        ];
    }

    public static function getPrivacyPolicyInformation(): array
    {
        return [
            Card::make()
            ->schema([
                Forms\Components\Placeholder::make('privacy_policy')
                ->label(fn(Get $get) => new HtmlString('
                <p style="margin: 15px; color:#00293C; font-weight: bold; text-decoration: underline font-size: 30px"> Privacy Policy</p>
                <p>1.Finta Tech Limited takes your privacy very seriously. This Privacy Policy explains what personal information we collect, with whom we share it, and how you
                        (the user of the Service) can prevent us from sharing certain information with certain parties. You should read this Privacy Policy in its entirety.</p>
                        <p style="margin-bottom: 15px; color:#00293C; font-weight: bold">Data We Collect</p>
                        <p>Finta Tech Limited obtains most non-public personal information directly from individuals or agents by telephone, by use of application forms or
                        electronically. Finta Tech Limited may obtain the following information:</p>
                       
                       <ol>
                       <li>First name, last name and title</li>
                       <li>Contact information including home address, email address, business address, home telephone numbers, business telephone number</li>
                       <li>ID numbers, Pin numbers and business registration numbers</li>
                       <li>Individual\'s accounts transactions and any other interactions through Finta Tech Limited.</li>
                       </ol>
                       <p style="margin: 15px; color:#00293C; font-weight: bold">Use and security of information</p>
                       <p>Finta Tech collects personal information:</p>
                       <ol>
                       <li>To verify an individual\'s identity and personal information.</li>
                       <li>To assess an individual\'s application to maintain a financial service.</li>
                       <li>To improve our products and services.</li>
                       <li>To conduct product and market research</li>
                       </ol>
                       <p>A range of security measures including information access restrictions, internal data classification Policy and record Management Policy, are in place and are
                        designed to prevent the misuse, interference, loss, unauthorized access, modification or disclosure of the individual personal information. We hold personal
                        information in physical and electronic forms at our own premises.</p>'))
                ->columnSpanFull()
                ->content(''), 
            Forms\Components\Placeholder::make('personal_info')
                ->label(fn(Get $get) => new HtmlString('
                <p style="margin: 15px; color:#00293C; font-weight: bold">Disclosure of personal information and Confidentiality</p>
                <p>Finta Tech Limited will not sell, distribute or lease a member\'s personal information to third parties unless the Finta Tech Limited believes it necessary for the
                    conduct of its business, or are required by law to do so. Except in those specific, limited situations, Finta Tech Limited will not make any disclosures of
                    non-public personal information without a member\'s consent.</p>'))
                ->columnSpanFull()
                ->content(''), 
            Forms\Components\Placeholder::make('terms')
                ->label(fn(Get $get) => new HtmlString('<p style="margin-bottom: 15px; color:#00293C; font-weight: bold">Terms used in this Privacy Policy shall have the following meanings:</p>
                <p>"Authorities" includes any judicial, administrative, public or regulatory body, any government, any Tax Authority, securities or futures exchange, court, central
                bank or law enforcement body, or any of their agents with jurisdiction over Finta Tech Limited.</p>
                <p>"Compliance Obligations" means obligations of Finta Tech Limited to comply with: (a) Laws or international guidance and internal policies or procedures,
                (b) any demand from Authorities or reporting, disclosure or other obligations under Laws, and (c) Laws requiring us to verify the identity of our customers.</p>
                <p>"Customer" or "User" means any individual within the Republic of Kenya to which Finta Tech Limited provides its products or services. 
                <p>"Customer Information" means your Personal Data, confidential information, and/ or Tax Information, including relevant information about you, your transactions, your
                use of our products and services, and your relationships with Finta Tech Limited .
                <p>"Financial Crime" means money laundering, terrorist financing, bribery, corruption, tax evasion, fraud, evasion of economic or trade sanctions, and/ or any
                acts or attempts to circumvent or violate any Laws relating to these matters.
                <p>"Laws" include any local law, regulation, judgment or court order, voluntary code, sanctions regime, an agreement between any member of Finta Tech Limited
                and an Authority, or agreement or treaty between Authorities and applicable to Finta Tech Limited.
                <p>"Personal Data" or "Personal Information" refers to any information whether recorded in a material form or not, from which the identity of an individual is
                apparent or can be reasonably and directly ascertained by the entity holding the information, or when put together with other information would directly and
                certainly identify an individual.
                <p>"Sensitive Personal Information" refers to Personal Information (1) about an individual\'s race, ethnic origin, marital status, age, color, and religious,
                philosophical or political affiliations; (2) about an individual\'s health, education, genetic or sexual life of a person, or to any proceeding for any offense
                committed or alleged to have been committed by such person, the disposal of such proceedings, or the sentence of any court in such proceedings; (3) issued by
                government agencies peculiar to an individual which includes, but not limited to, social security numbers, previous or current health records, licenses or its
                denials, suspension or revocation, and tax returns; and (4) specifically established by an executive order or other legislative act to be kept classified.
                <p>"Tax Authorities" means Kenyan tax, revenue or monetary authorities.</p>
                <p>"Tax Information" means documentation or information about your tax status. "We", "Our" and "Us" refer to Finta Tech Limited.
                Reference to the singular includes the plural (and vice versa).</p>'))
                ->columnSpanFull()
                ->content(''), 
            Forms\Components\Placeholder::make('consent')
                ->label(fn(Get $get) => new HtmlString('<p style="margin-bottom: 15px; color:#00293C; font-weight: bold">By signing hereinunder, you consent to the following:</p>
                <p>I hereby give consent to the collection and processing of my personal information for legitimate business purposes, included but not limited to
                    determining my credit score and providing a loan.</p>
                    <ul>
                    <li>1.I hereby certify that all the information provided by me is true and correct to the best of my knowledge, and that any misrepresentations or falsity
                    therein will be considered as an act to defraud Finta Tech Limited and its partners. I authorize Finta Tech Limited to verify and investigate the above
                    statements/information as may be required, from the references provided and other reasonable sources. For this purpose, I hereby waive my rights on
                    the confidentiality of client information and expressly consent to the processing of any personal information and records relating to me that might be
                    obtained from third parties, including government agencies, my employer, credit bureaus, business associates and other entities you may deem proper
                    and sufficient in the conduct of my business, sensitive or otherwise, for the purpose of determining my eligibility for a loan which I am applying for.
                    </li>
                    <li>2. I further agree that this application and all supporting documents and any other information obtained relative to this application shall be used by and
                    communicated to Finta Tech Limited, and shall remain its property whether or not my credit score is determined, or the loan is granted.</li>
                    <li>3. I expressly and unconditionally authorize Finta Tech Limited to disclose to any bank or affiliate and other financial institution any information
                    regarding me. In particular, I hereby acknowledge and authorize:</li></ul>
                    <ul>
                    <li>a) the regular submission and disclosure of my basic credit data as well as any updates or corrections thereof; and</li>
                    <li>b) the sharing of my basic credit data with other lenders, and duly accredited credit reporting agencies.</li>
                    </ul>
                    </p>
                    <p style="margin-bottom: 8px; color:#00293C; font-weight: bold">Read and Accepted</p>
                    </p>'))
                ->columnSpanFull()
                ->content(''), 
            ])
            ->columnSpan('full'),
            SignaturePad::make('privacy_signature')
               ->label('Privacy Signature')
                ->dotSize(2.0)
               ->lineMinWidth(0.5)
               ->backgroundColor('rgba(0,0,0,0)')  // Background color on light mode
               ->backgroundColorOnDark('#f0a')
               ->penColor('#0000FF')
               ->penColorOnDark('#fff') 
               ->lineMaxWidth(2.5)
               ->throttle(16)
               ->minDistance(5)
               ->required()
               ->velocityFilterWeight(0.7) 
                ->downloadable()                    // Allow download of the signature (defaults to false)
               ->downloadableFormats([             // Available formats for download (defaults to all)
                   DownloadableFormat::PNG,
                   DownloadableFormat::JPG,
                   DownloadableFormat::SVG,
               ]),
        ];
    }

    public static function getAdminInformation(): array
    {
        return [
            Card::make()
            ->schema([  
                Forms\Components\Checkbox::make('id_verified')
                ->label('ID Verified?')
                ->required(),
                Forms\Components\Checkbox::make('address_verified')
                ->label('Address Verified?')
                ->required(),
                Forms\Components\Checkbox::make('referees_contacted')
                ->label('Client Referees Contacted?')
                ->required(),
                Forms\Components\Select::make('loan_officer_id')
                ->label('Relationship Officer')
                ->relationship('loan_officer','name',
                    modifyQueryUsing:  function (Builder $query) {
                        $tenantModel = Filament::getTenant();
                        $query->whereHas('branches', function (Builder $query) use ($tenantModel) {
                            $query->whereHas('users', function (Builder $query) use ($tenantModel) {
                                $query->where('branch_id', $tenantModel->id);
                            });
                        });
                    })
                    ->preload()
                ->required(),
            ])
            ->columnSpan('full')
        ];
    }

    public static function getClientDetailsSummary(): array
    {
        return [
            Forms\Components\Card::make('Personal Details')
            ->columns(3)
                ->schema([
                    Forms\Components\Placeholder::make('client_type')
                        ->label('Client Type')
                        ->content(fn (Forms\Get $get): ?string => $get('client_type_id') ? ClientType::find($get('client_type_id'))->name : null),
                    Forms\Components\Placeholder::make('title_id')
                        ->label('Title')
                        ->content(fn (Forms\Get $get): ?string => $get('title_id') ? Title::find($get('title_id'))->name : null),
                    Forms\Components\Placeholder::make('first_name')
                        ->label('First Name')
                        ->content(fn (Forms\Get $get): ?string => $get('first_name')),
                    Forms\Components\Placeholder::make('middle_name')
                        ->label('Middle Name')
                        ->content(fn (Forms\Get $get): ?string => $get('middle_name')),
                    Forms\Components\Placeholder::make('last_name')
                        ->label('Last Name')
                        ->content(fn (Forms\Get $get): ?string => $get('last_name')),
                    Forms\Components\Placeholder::make('id_number')
                        ->label('ID Number')
                        ->content(fn (Forms\Get $get): ?string => $get('id_number')),
                    Forms\Components\Placeholder::make('aka')
                        ->label('AKA')
                        ->content(fn (Forms\Get $get): ?string => $get('aka')),
                    Forms\Components\Placeholder::make('id_type')
                        ->label('ID Type')
                        ->content(fn (Forms\Get $get): ?string => $get('id_type')),
                    Forms\Components\Placeholder::make('gender')
                        ->label('Gender')
                        ->content(fn (Forms\Get $get): ?string => $get('gender')),
                    Forms\Components\Placeholder::make('marital_status')
                        ->label('Marital Status')
                        ->content(fn (Forms\Get $get): ?string => $get('marital_status')),
                    Forms\Components\Placeholder::make('education_level')
                        ->label('Education Level')
                        ->content(fn (Forms\Get $get): ?string => $get('education_level')),
                    Forms\Components\Placeholder::make('profession_id')
                        ->label('Profession')
                        ->content(fn (Forms\Get $get): ?string => $get('profession_id') ? Profession::find($get('profession_id'))->name : null),
                    Forms\Components\Placeholder::make('mobile')
                        ->label('Mobile')
                        ->content(fn (Forms\Get $get): ?string => $get('mobile')),
                    Forms\Components\Placeholder::make('other_mobile_no')
                        ->label('Other Mobile')
                        ->content(fn (Forms\Get $get): ?string => $get('other_mobile_no')),
                    Forms\Components\Placeholder::make('email')
                        ->label('Email')
                        ->content(fn (Forms\Get $get): ?string => $get('email')),
                    Forms\Components\Placeholder::make('kra_pin')
                        ->label('KRA PIN')
                        ->content(fn (Forms\Get $get): ?string => $get('kra_pin')),
                    Forms\Components\Placeholder::make('postal_code')
                        ->label('Postal Code')
                        ->content(fn (Forms\Get $get): ?string => $get('postal_code')),
                    Forms\Components\Placeholder::make('dob')
                        ->label('Date of Birth')
                        ->content(fn (Forms\Get $get): ?string => $get('dob')),
                    Forms\Components\Placeholder::make('source_of_income')
                        ->label('Source of Income')
                        ->content(fn (Forms\Get $get): ?string => $get('source_of_income')),
                    Forms\Components\Placeholder::make('type_of_tech')
                        ->label('Type of Technology')
                        ->content(fn (Forms\Get $get): ?string => $get('type_of_tech')),
                    // Forms\Components\ViewField::make('rating')
                    //     ->view('filament.forms.components.journal-entry-repeater')
                    //     ->registerActions([
                    //         Action::make('setMaximum')
                    //             ->icon('heroicon-m-star')
                    //             ->action(function (Forms\Get $get) {
                    //                 dd($get);
                    //             }),
                    //     ]),
                    ])->headerActions([
                        
                        
                    ])->compact(),

                 
                Forms\Components\Card::make('Address Details')
                       ->columns(3)
                        ->schema([
                    Forms\Components\Placeholder::make('address_type')
                        ->label('Address Type')
                        ->content(function (Forms\Get $get): ?string {
                            $addresses = $get('addresses');
                            return is_array($addresses) && !empty($addresses[0]['address_type']) ? $addresses[0]['address_type'] : null;
                        }),
                    Forms\Components\Placeholder::make('country')
                        ->label('Country')
                        ->content(function (Forms\Get $get): ?string {
                            $addresses = $get('addresses');
                            return is_array($addresses) && !empty($addresses[0]['country']) ? $addresses[0]['country'] : null;
                        }),
                    Forms\Components\Placeholder::make('county')
                        ->label('County')
                        ->content(function (Forms\Get $get): ?string {
                            $addresses = $get('addresses');
                            return is_array($addresses) && !empty($addresses[0]['county']) ? $addresses[0]['county'] : null;
                        }),
                    Forms\Components\Placeholder::make('sub_county')
                        ->label('Sub County')
                        ->content(function (Forms\Get $get): ?string {
                            $addresses = $get('addresses');
                            return is_array($addresses) && !empty($addresses[0]['sub_county']) ? $addresses[0]['sub_county'] : null;
                        }),
                    Forms\Components\Placeholder::make('ward')
                        ->label('Ward')
                        ->content(function (Forms\Get $get): ?string {
                            $addresses = $get('addresses');
                            return is_array($addresses) && !empty($addresses[0]['ward']) ? $addresses[0]['ward'] : null;
                        }),
                    Forms\Components\Placeholder::make('village')
                        ->label('Village')
                        ->content(function (Forms\Get $get): ?string {
                            $addresses = $get('addresses');
                            return is_array($addresses) && !empty($addresses[0]['village']) ? $addresses[0]['village'] : null;
                        }),
                    Forms\Components\Placeholder::make('street')
                        ->label('Street')
                        ->content(function (Forms\Get $get): ?string {
                            $addresses = $get('addresses');
                            return is_array($addresses) && !empty($addresses[0]['street']) ? $addresses[0]['street'] : null;
                        }),
                    Forms\Components\Placeholder::make('landmark')
                        ->label('Landmark')
                        ->content(function (Forms\Get $get): ?string {
                            $addresses = $get('addresses');
                            return is_array($addresses) && !empty($addresses[0]['landmark']) ? $addresses[0]['landmark'] : null;
                        }),
                    Forms\Components\Placeholder::make('latitude')
                        ->label('Latitude')
                        ->content(function (Forms\Get $get): ?string {
                            $addresses = $get('addresses');
                            return is_array($addresses) && !empty($addresses[0]['latitude']) ? (string)$addresses[0]['latitude'] : null;
                        }),
                    Forms\Components\Placeholder::make('longitude')
                        ->label('Longitude')
                        ->content(function (Forms\Get $get): ?string {
                            $addresses = $get('addresses');
                            return is_array($addresses) && !empty($addresses[0]['longitude']) ? (string)$addresses[0]['longitude'] : null;
                        }),
                    Forms\Components\Placeholder::make('building')
                        ->label('Building')
                        ->content(function (Forms\Get $get): ?string {
                            $addresses = $get('addresses');
                            return is_array($addresses) && !empty($addresses[0]['building']) ? $addresses[0]['building'] : null;
                        }),
                    Forms\Components\Placeholder::make('floor_no')
                        ->label('Floor No')
                        ->content(function (Forms\Get $get): ?string {
                            $addresses = $get('addresses');
                            return is_array($addresses) && !empty($addresses[0]['floor_no']) ? $addresses[0]['floor_no'] : null;
                        }),
                    Forms\Components\Placeholder::make('house_no')
                        ->label('House No')
                        ->content(function (Forms\Get $get): ?string {
                            $addresses = $get('addresses');
                            return is_array($addresses) && !empty($addresses[0]['house_no']) ? $addresses[0]['house_no'] : null;
                        }),
                    Forms\Components\Placeholder::make('estate')
                        ->label('Estate')
                        ->content(function (Forms\Get $get): ?string {
                            $addresses = $get('addresses');
                            return is_array($addresses) && !empty($addresses[0]['estate']) ? $addresses[0]['estate'] : null;
                        }),
                        ])->headerActions([
                            Forms\Components\Actions\Action::make('edit')
                            ->alpineClickHandler("step = 'Address Details'")
                            ->icon('heroicon-o-pencil')
                            ->label('Edit'),
                        ])->compact(),
             Forms\Components\Card::make('Next of Kin Details')
                ->columns(3)
                ->schema([
                    Forms\Components\Placeholder::make('next_of_kin_name')
                        ->label('Next of Kin Name')
                        ->content(function (Forms\Get $get): ?string {
                            $nextOfKins = $get('next_of_kins');
                            return is_array($nextOfKins) && !empty($nextOfKins[0]['name']) ? $nextOfKins[0]['name'] : null;
                        }),
                    Forms\Components\Placeholder::make('next_of_kin_relationship')
                        ->label('Next of Kin Relationship')
                        ->content(function (Forms\Get $get): ?string {
                            $nextOfKins = $get('next_of_kins');
                            return is_array($nextOfKins) && !empty($nextOfKins[0]['relationship']) ? $nextOfKins[0]['relationship'] : null;
                        }),
                    Forms\Components\Placeholder::make('next_of_kin_mobile')
                        ->label('Next of Kin Mobile')
                        ->content(function (Forms\Get $get): ?string {
                            $nextOfKins = $get('next_of_kins');
                            return is_array($nextOfKins) && !empty($nextOfKins[0]['mobile']) ? $nextOfKins[0]['mobile'] : null;
                        }),
                ])
                ->headerActions([
                    // Forms\Components\Actions\Action::make('edit')
                    // ->alpineClickHandler("step = 'Next of Kin Details'")
                    // ->icon('heroicon-o-pencil')
                    // ->label('Edit'),
                ])
                ->compact(),   
            
        ];
    }
    
   

    public static function getRelations(): array
    {
        return [
            RepaymentAccountRelationManager::class,
            LoansRelationManager::class,
            SmsRelationManager::class,
            AddressesRelationManager::class,
            SpousesRelationManager::class,
            RefereesRelationManager::class,
            NextOfKinsRelationManager::class,
            FilesRelationManager::class,
        ];
    }


    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClients::route('/'),
            'create' => Pages\CreateClient::route('/create'),
            'edit' => Pages\EditClient::route('/{record}/edit'),
            'view' => Pages\ViewClient::route('/{record}'),
             'manage_employment_info' => Pages\ManageEmploymentInfo::route('/{record}/manage-employment-info'),
        ];
    }
    
//         public static function getRecordSubNavigation(Page $page): array
// {
//     return $page->generateNavigationItems([
//         Pages\ViewClient::class,
//         Pages\EditClient::class,
//          Pages\ManageEmploymentInfo::class,
//     ]);
// }

    public static function getGlobalSearchResultUrl(Model $record): string
    {
        return ClientResource::getUrl('view', ['record' => $record]);
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
        return ['first_name', 'middle_name', 'last_name',  'account_number', 'mobile'];
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
            'change_limit',
        ];
    }
}
