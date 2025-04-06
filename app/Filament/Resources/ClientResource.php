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
use Pages\ApproveClient;
use App\Enums\LoanStatus;
use App\Enums\TypeOfTech;
use App\Models\SubCounty;
use Pages\ApprovalClient;
use App\Models\ClientType;
use App\Models\Profession;
use Filament\Tables\Table;
use App\Enums\Relationship;
use App\Enums\MaritalStatus;
use App\Enums\EducationLevel;
use App\Enums\SourceOfIncome;
use App\Policies\ClientPolicy;
use Filament\Facades\Filament;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Awcodes\Curator\Models\Media;
use Awcodes\TableRepeater\Header;
use Dotswan\MapPicker\Fields\Map;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\DB;
use Filament\Forms\Components\Card;
use Illuminate\Support\Facades\URL;
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
use Illuminate\Support\Facades\Storage;
use App\Filament\Exports\ClientExporter;
use App\Filament\Imports\ClientImporter;
use Brick\PhoneNumber\PhoneNumberFormat;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\Split;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Forms\Components\DatePicker;
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
use App\Filament\Resources\ClientResource\Pages;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;
use Filament\Infolists\Components\Actions\Action;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Tables\Actions\Action as TableAction;
use Ysfkaya\FilamentPhoneInput\Tables\PhoneColumn;
use Awcodes\Curator\Components\Forms\CuratorPicker;
use Awcodes\TableRepeater\Components\TableRepeater;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Ysfkaya\FilamentPhoneInput\Infolists\PhoneEntry;
use Ysfkaya\FilamentPhoneInput\PhoneInputNumberType;
use Saade\FilamentAutograph\Forms\Components\SignaturePad;
use App\Filament\Resources\ClientResource\RelationManagers;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Filament\Forms\Components\Actions\Action as Deleteaction;
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


class ClientResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = Client::class;

    //protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'Maintenance';
    protected static ?string $navigationGroup = 'Clients Management';
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
            ->minValue(6)
            ->maxValue(8),
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
            ->required(),
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
        Forms\Components\TextInput::make('postal_code')
            ->label('Postal Code')
            ->maxLength(25),
        Forms\Components\DatePicker::make('dob')
            ->label('Date of Birth')
            ->required(),
        Forms\Components\Select::make('source_of_income')
            ->options(SourceOfIncome::class),
        Forms\Components\Select::make('type_of_tech')
            ->required()
            ->label('Type of Technology')
            ->options(TypeOfTech::class),
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
        CuratorPicker::make('photo')
            ->label('Avatar')
            ->buttonLabel('Upload Avatar')
            ->size('sm')
            ->imageResizeTargetWidth('200')
            ->imageResizeTargetHeight('200'),
        CuratorPicker::make('id_front')
            ->label('ID Front')
            ->buttonLabel('Upload ID Front')
            ->size('sm')
            ->imageResizeTargetWidth('200')
            ->imageResizeTargetHeight('200')
            ->required()
            ,
        CuratorPicker::make('id_back')
            ->label('ID Back')
            ->buttonLabel('Upload ID Back')
            ->size('sm')
            ->imageResizeTargetWidth('200')
            ->imageResizeTargetHeight('200')
            ->required(),
            Forms\Components\TextInput::make('notes')
            ->label('Notes')
            ->maxLength(255),
        SignaturePad::make('signature')
             ->dotSize(2.0)
            ->lineMinWidth(0.5)
            ->lineMaxWidth(2.5)
            ->throttle(16)
            ->minDistance(5)
            ->velocityFilterWeight(0.7) 
             ->downloadable()                    // Allow download of the signature (defaults to false)
            ->downloadableFormats([             // Available formats for download (defaults to all)
                DownloadableFormat::PNG,
                DownloadableFormat::JPG,
                DownloadableFormat::SVG,
            ]),
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
                    ->getStateUsing(function($record) {
                        $media = Media::where('id', $record->photo)->first();

                            if ($media) {
                                return $media->path;
                            } else {
                                return 'https://ui-avatars.com/api/?background=random&name=' . urlencode($record->fullname);
                            }
                    }),
                Tables\Columns\TextColumn::make('fullname')
                    ->label('Full Name')
                    ->searchable(['first_name', 'middle_name', 'last_name']),
                Tables\Columns\TextColumn::make('mobile')
                    ->searchable(),
                Tables\Columns\TextColumn::make('loan_officer.fullname')
                    ->label('Loan Officer')
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
                                ->label('Loan Officer')
                                ->options(User::pluck(DB::raw("CONCAT(first_name, ' ', last_name)"), 'id'))
                                ->searchable(),
                DateRangeFilter::make('created_at')
                                ->label('Joined Date')
                                ->withIndicator(),


            ], layout: FiltersLayout::AboveContent)


            ->actions([
                ActionGroup::make([
                    Tables\Actions\Action::make('status')
                    ->label('Change Status')
                    ->icon('heroicon-o-arrow-path')
                    ->modalHeading('Change Client Status')
                    ->visible(function(Client $record) {
                        $policy = new ClientPolicy();
                        return $policy->update(Auth::user(), $record);
                    })
                    ->form([
                        Forms\Components\Select::make('status')
                            ->options(Status::class)
                            ->required(),
                    ])
                    ->action(function (Client $record,array $data) {
                        $record->changeStatus($data['status']);
                        Notification::make()
                                    ->success()
                                    ->title('Status Changed')
                                    ->body('The status has been changed successfully.')
                                    ->send();

                    })
                    ->color('success')
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
                            ->getStateUsing(function($record) {
                                $media = Media::where('id', $record->photo)->first();

                                    if ($media) {
                                        return $media->path;
                                    } else {
                                        return 'https://ui-avatars.com/api/?background=random&name=' . urlencode($record->fullname);
                                    }
                            }),
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
                            ->label('Signature')
                            ->height(200)
                            ->width(200),
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
                        // Action::make('Change Status')
                        //     ->label('Change Status')
                        //     ->form([
                        //             Forms\Components\Select::make('status')
                        //                 ->options(Status::class)
                        //                 ->required(),
                        //            ])
                        //            ->action(function (Client $record, $data) {
                        //         $record->changeStatus($data['status']);

                        //         Notification::make()
                        //             ->success()
                        //             ->title('Status Changed')
                        //             ->body('The status has been changed successfully.')
                        //             ->send();

                        //     })
                        //     ->requiresConfirmation(),

                        // Action::make('Change Limit')
                        //     ->label('Change Limit')
                        //     ->visible(function(Client $record) {
                        //         $policy = new ClientPolicy();
                        //         return $policy->changeLimit(Auth::user(), $record);
                        //     })
                        //     ->form([
                        //             Forms\Components\TextInput::make('suggested_loan_limit')
                        //                 ->numeric()
                        //                 ->required(),
                        //            ])
                        //     ->action(function (Client $record, $data) {
                        //         $record->suggested_loan_limit = $data['suggested_loan_limit'];
                        //         $record->save();

                        //         Notification::make()
                        //             ->success()
                        //             ->title('Loan Limit Changed')
                        //             ->body('The Loan Limit has been changed successfully.')
                        //             ->send();

                        //     })
                        //     ->requiresConfirmation(),

                    //   Action::make('Check limit')
                    //         ->label('Refresh Loan Limit')
                    //         ->action(function (Client $record) {
                    //             $score = $record->calculatePaymentHabitScore();
                    //             $record->calculateSuggestedLoanLimit($score);

                    //             Notification::make()
                    //                 ->success()
                    //                 ->title('Check Loan Limit')
                    //                 ->body('The Loan Limit has Been refreshed successfully.')
                    //                 ->send();

                    //         })
                    //         ->requiresConfirmation(),
                    ])

                    ->schema([
                        Infolists\Components\TextEntry::make('status')
                            ->color('info'),
                        Infolists\Components\TextEntry::make('address')
                            ->color('info'),
                        Infolists\Components\TextEntry::make('city')
                            ->color('info'),
                        Infolists\Components\TextEntry::make('state')
                            ->color('info'),
                        Infolists\Components\TextEntry::make('email')
                            ->color('info')
                            ->columnSpan(2),
                        Infolists\Components\TextEntry::make('dob')
                            ->color('info')
                            ->label('Date of Birth'),
                        Infolists\Components\TextEntry::make('dob')
                            ->color('info')
                            ->since()
                            ->dateTimeTooltip()
                            ->label('Age'),
                        Infolists\Components\TextEntry::make('notes')
                            ->color('info'),
                        Infolists\Components\TextEntry::make('created_at')
                            ->color('info')
                            ->label('Created On'),
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
                    ->maxLength(255),
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
                Forms\Components\TextInput::make('postal_code')
                    ->label('Postal Code')
                    ->maxLength(25),
                Forms\Components\DatePicker::make('dob')
                    ->label('Date of Birth')
                    ->required(),
                Forms\Components\Select::make('source_of_income')
                    ->options(SourceOfIncome::class),
                Forms\Components\Select::make('type_of_tech')
                    ->required()
                    ->label('Type of Technology')
                    ->options(TypeOfTech::class),
                CuratorPicker::make('photo')
                    ->label('Avatar')
                    ,
                CuratorPicker::make('signature')
                    ->label('Signature')
                    ->required()
                    ,
                CuratorPicker::make('id_front')
                    ->label('ID Front')
                    ->required()
                    ,
                CuratorPicker::make('id_back')
                    ->label('ID Back')
                    ->required(),
                    
                    ])->columns(3),
                ];
    }

    public static function getAddressInformation(): array
    {
        return [
            Repeater::make('addresses')
            ->columns(3)
            ->addActionLabel('Add Address')
            ->label('Addresses')
            ->minItems(1)
            ->maxItems(5)
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
            Forms\Components\Select::make('county')
                ->label('County')
                ->options(County::all()->pluck('name', 'id'))
                ->live()
                ->required(),
            Forms\Components\Select::make('sub_county')
                ->label('Sub County')
                ->live()
                ->options(fn (Get $get) => SubCounty::query()
                        ->where('county_id', $get('county'))
                        ->get()
                        ->pluck('name', 'id'))
                ->required(),
            Forms\Components\Select::make('ward')
               ->options(fn (Get $get) => Town::query()
                        ->where('sc_id', $get('sub_county'))
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
            Map::make('location')
            ->live()
                ->label('Location')
                ->showMyLocationButton(true)
                ->liveLocation(true, true, 10000)  // Updates live location every 10 seconds
                ->showMarker()
                ->draggable()
                ->columnSpanFull()
                ->zoom(15)
                ->minZoom(0)
                ->maxZoom(28)
                ->clickable(true)
                ->afterStateHydrated(function ($state, $record, Set $set): void {
                    $set('location', ['lat' => $record?->latitude, 'lng' => $record?->longitude]);
                })
                ->afterStateUpdated(function (Set $set, ?array $state): void {
                    $set('latitude', $state['lat']);
                    $set('longitude', $state['lng']);
                   
                })
                ->required(),
            Forms\Components\TextInput::make('latitude')
            ->readOnly(),
            Forms\Components\TextInput::make('longitude')
            ->readOnly(),
            CuratorPicker::make('image')
                ->label('Image')
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
            ->addActionLabel('Add Next of Kin')
            ->columns(3)
                ->schema([
                    Forms\Components\TextInput::make('name')
                    ->label('Full Name')
                    ->required()
                    ->maxLength(255),
                FilamentPhoneNumbers\Forms\Components\PhoneNumber::make('mobile')
                    ->label('Mobile')
                    ->region('KE')
                    ->displayFormat(PhoneNumberFormat::E164)
                    ->mask('9999999999')
                    ->required()
                    ->maxLength(20),
                Forms\Components\Select::make('relationship')
                    ->label('Relationship')
                    ->options([
                        'spouse' => 'Spouse',
                        'parent' => 'Parent',
                        'child' => 'Child',
                        'sibling' => 'Sibling',
                        'friend' => 'Friend',
                        'other' => 'Other'
                    ])
                    ->required(),
                ])
           
        ];
    }

    public static function getSpouseInformation(): array
    {
        return [
            Card::make()
            ->schema([  
                Forms\Components\TextInput::make('spouce_name')
                ->label('Spouse Name')
                ->required()
                ->maxLength(255),
                Forms\Components\TextInput::make('spouce_id')
                ->label('Spouse ID Number')
                ->required()
                ->maxLength(50),
            FilamentPhoneNumbers\Forms\Components\PhoneNumber::make('spouce_mobile')
                ->label('Spouse Mobile')
                ->region('KE')
                ->displayFormat(PhoneNumberFormat::E164)
                ->mask('9999999999')
                ->required()
                ->maxLength(20),
            Forms\Components\TextInput::make('spouce_occupation')
                ->label('Spouse Occupation')
                ->maxLength(100),
                CuratorPicker::make('consent_form')
                ->label('Consent Form')
                ->required(),
            ])
            ->columns(3),
        ];
    }

    public static function getRefereesInformation(): array
    {
        return [
            Repeater::make('referees')
            ->addActionLabel('Add Referees')
                ->schema([
                        Forms\Components\TextInput::make('referee_name')
                        ->label('Referee Name')
                        ->maxLength(255),
                    FilamentPhoneNumbers\Forms\Components\PhoneNumber::make('referee_mobile')
                        ->label('Referee Mobile')
                        ->region('KE')
                        ->displayFormat(PhoneNumberFormat::E164)
                        ->mask('9999999999')
                        ->required(),
                    Forms\Components\Select::make('referee_relationship')
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
            Repeater::make('client_leads')
            ->addActionLabel('Add Client Lead')
            ->columns(3)
            ->schema([
                Forms\Components\Select::make('source')
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
                    ->visible(fn (Get $get): bool => $get('source') === 'existing_client')
                    ->options(Client::all()->pluck('full_name', 'id'))
                    ->required(),
                Forms\Components\Textarea::make('others')
                    ->label('Others')
                    ->visible(fn (Get $get): bool => $get('source') === 'other')
                    ->required(),

            ])
            ->columns(3),
        ];
    }

    public static function getAdminInformation(): array
    {
        return [
            Card::make()
            ->schema([  
                Forms\Components\Checkbox::make('terms_and_condition')
                ->label('Clients accepts Terms and Conditions?')
                ->required(),
                Forms\Components\Checkbox::make('privacy_policy')
                ->label('Clients accepts Privacy Policy?')
                ->required(),
            Forms\Components\Checkbox::make('signature_confirmed')
                ->label('Client Signature Confirmed?')
                ->required(),
                Forms\Components\Checkbox::make('referees_contacted')
                ->label('Client Referees Contacted?')
                ->required(),
                CuratorPicker::make('reg_form')
                ->label('Registration Form')
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
                    
                    ])->headerActions([
                        Forms\Components\Actions\Action::make('edit')
                        ->alpineClickHandler("step = 'Personal Details'")
                        ->icon('heroicon-o-pencil')
                        ->label('Edit'),
                        
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
               
            
        ];
    }
    
   

    public static function getRelations(): array
    {
        return [
            LoansRelationManager::class,
            NextOfKinsRelationManager::class,
            FilesRelationManager::class,
            AddressesRelationManager::class,
            SpousesRelationManager::class,
            RefereesRelationManager::class,
            SmsRelationManager::class,

        ];
    }


    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClients::route('/'),
            'create' => Pages\CreateClient::route('/create'),
            'edit' => Pages\EditClient::route('/{record}/edit'),
            'view' => Pages\ViewClient::route('/{record}'),
        ];
    }


    public static function getRecordSubNavigation(Page $page): array
{
    return $page->generateNavigationItems([
        Pages\ViewClient::class,
        Pages\EditClient::class,
    ]);
}

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
