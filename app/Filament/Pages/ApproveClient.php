<?php

namespace App\Filament\Pages;

use App\Models\Client;
use Filament\Pages\Page;
use Filament\Tables\Table;
use Filament\Facades\Filament;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Repeater;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\ImageColumn;
use Filament\Forms\Components\FileUpload;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Wizard\Step;
use App\Jobs\SendRegistrationNotificationJob;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Tables\Concerns\InteractsWithTable;
use Awcodes\Curator\Components\Forms\CuratorPicker;
use Joaopaulolndev\FilamentPdfViewer\Forms\Components\PdfViewerField;

class ApproveClient extends Page implements HasTable
{   

    use InteractsWithTable;
  
    protected static ?string $navigationLabel = 'Approve Clients';
    protected static ?string $navigationGroup = 'Clients Management';
    protected static ?int $navigationSort = 2;


    protected static string $view = 'filament.pages.approve-client';


    public function table(Table $table): Table
    {
        return $table
            ->query(Client::query()->where('status', 'pending'))
            ->columns([
                TextColumn::make('full_name'),
                TextColumn::make('mobile'),
                TextColumn::make('loan_officer.fullname')
                ->label('Loan Officer'),
                TextColumn::make('status')
                ->badge()
                ->label('Status'),
            ])
            
            ->actions([
                ActionGroup::make([
                Action::make('approve')
                    ->label('Approve Client')
                    ->icon('heroicon-o-check')
                    ->action(function (Client $record) {
                        $record->changeStatus('approved');
                        SendRegistrationNotificationJob::dispatch($record);
                        Notification::make()
                                    ->success()
                                    ->title('Client Approved')
                                    ->body('The client has been approved successfully.')
                                    ->send();
                    })
                    ->color('success')
                    ->fillForm(fn (Client $record): array => [
                        'first_name' => $record->first_name,
                        'last_name' => $record->last_name,
                        'middle_name' => $record->middle_name,
                        'account_number' => $record->account_number,
                        'id_number' => $record->id_number,
                        'id_type' => $record->id_type,
                        'gender' => $record->gender,
                        'marital_status' => $record->marital_status,
                        'education_level' => $record->education_level,
                        'profession_id' => $record->profession->name,
                        'mobile' => $record->mobile,
                        'other_mobile_no' => $record->other_mobile_no,
                        'email' => $record->email,
                        'kra_pin' => $record->kra_pin,
                        'dob' => $record->dob,
                        'source_of_income' => $record->source_of_income,
                        'type_of_tech' => $record->type_of_tech,
                        'loan_officer' => $record->loan_officer->full_name,
                        'signature' => $record->signature,
                        'id_front' => $record->id_front,
                        'id_back' => $record->id_back,
                        'loan_officer_id' => $record->loan_officer->full_name,
                        'addresses' => $record->addresses && $record->addresses->count() > 0 ? $record->addresses->map(function($address) {
                            // Load the address with its relationships if they're not already loaded
                            if (!$address->relationLoaded('county') || !$address->relationLoaded('subCounty') || !$address->relationLoaded('ward')) {
                                $address->load(['county', 'subCounty', 'ward']);
                            }
                            
                            return [
                                'address_type' => $address->address_type,
                                'country' => $address->country,
                                'county_id' => $address->county ? $address->county->name : null,
                                'sub_county_id' => $address->subCounty ? $address->subCounty->name : null,
                                'ward_id' => $address->ward ? $address->ward->name : null,
                                'village' => $address->village,
                                'street' => $address->street,
                                'landmark' => $address->landmark,
                                'building' => $address->building,
                                'floor_no' => $address->floor_no,
                                'house_no' => $address->house_no,
                                'estate' => $address->estate,
                                'latitude' => $address->latitude,
                                'longitude' => $address->longitude,
                                'image' => $address->image,
                                'image_description' => $address->image_description,
                            ];
                        })->toArray() : [],
                        'spouse_name' => $record->spouse->name ?? null,
                        'spouse_mobile' => $record->spouse->mobile ?? null,
                        'spouse_occupation' => $record->spouse->occupation ?? null,
                        'consent_form' => $record->spouse->consent_form ?? null,
                        'lead_source' => $record->lead_source ?? null,
                        'existing_client' => $record->existing_client ?? null,
                        'terms_and_condition' => $record->terms_and_condition ?? null,
                        'privacy_policy' => $record->privacy_policy ?? null,
                        'signature_confirmed' => $record->signature_confirmed ?? null,
                        'referees_contacted' => $record->referees_contacted ?? null,
                        'reg_form' => $record->reg_form ?? null,
                        // Get the first next of kin data
                        'next_of_kin' => $record->next_of_kins && $record->next_of_kins->count() > 0 ? $record->next_of_kins->map(function($nok) {
                            return [
                                'first_name' => $nok->first_name ?? null,
                                'middle_name' => $nok->middle_name ?? null,
                                'last_name' => $nok->last_name ?? null,
                                'relationship' => $nok->client_relationship && $nok->client_relationship->name ? $nok->client_relationship->name : null,
                                'mobile' => $nok->mobile ?? null,
                                'email' => $nok->email ?? null,
                                'gender' => $nok->gender ?? null,
                                'address' => $nok->address ?? null
                            ];
                        })->toArray() : [],
                        'referees' => $record->referees && $record->referees->count() > 0 ? $record->referees->map(function($ref) {
                            return [
                                'name' => $ref->name ?? null,
                                'mobile' => $ref->mobile ?? null,
                                'email' => $ref->email ?? null,
                                'address' => $ref->address ?? null,
                                'relationship' => $ref->client_relationship->name ?? null,
                            ];
                        })->toArray() : [],
                    ])
                    ->steps([
                        Step::make('Bio Data')
                            ->description('Personal Information')
                            ->schema([
                                TextInput::make('first_name')
                                   ->disabled(),
                                TextInput::make('last_name')
                                    ->disabled(),
                                TextInput::make('middle_name')
                                    ->disabled(),
                                TextInput::make('aka')
                                    ->disabled(),
                                TextInput::make('account_number')
                                    ->disabled(),
                                TextInput::make('id_number')
                                    ->label('ID Number')
                                    ->disabled(),
                                TextInput::make('id_type')
                                    ->label('ID Type')
                                    ->disabled(),
                                TextInput::make('gender')
                                    ->label('Gender')
                                    ->disabled(),
                                TextInput::make('marital_status')
                                    ->label('Marital Status')
                                    ->disabled(),
                                TextInput::make('education_level')
                                    ->label('Education Level')
                                    ->disabled(),
                                TextInput::make('profession_id')
                                    ->label('Profession')
                                    ->disabled(),
                                TextInput::make('mobile')
                                    ->label('Mobile')
                                    ->disabled(),
                                TextInput::make('other_mobile_no')
                                    ->label('Other Mobile')
                                    ->disabled(),
                                TextInput::make('email')
                                    ->label('Email')
                                    ->disabled(),
                                TextInput::make('kra_pin')
                                    ->label('KRA PIN')
                                    ->disabled(),
                                TextInput::make('dob')
                                    ->label('Date of Birth')
                                    ->disabled(),
                                TextInput::make('source_of_income')
                                    ->label('Source of Income')
                                    ->disabled(),
                                TextInput::make('type_of_tech')
                                    ->label('Type of Technology')
                                    ->disabled(),
                                TextInput::make('signature')
                                    ->label('Signature')
                                    ->disabled(),
                                TextInput::make('id_front')
                                    ->label('ID Front')
                                    ->disabled(),
                                TextInput::make('id_back')
                                    ->label('ID Back')
                                    ->disabled(),
                            ])
                            ->columns(3),
                        Step::make('Address Details')
                            ->description('Add Address')
                            ->schema([
                                Repeater::make('addresses')
                                    ->schema([
                                        TextInput::make('address_type')
                                            ->label('Address Type')
                                            ->disabled(),
                                        TextInput::make('country')
                                            ->label('Country')
                                            ->disabled(),
                                        TextInput::make('county_id')
                                            ->label('County')
                                            ->disabled(),
                                        TextInput::make('sub_county_id')
                                            ->label('Sub County')
                                            ->disabled(),
                                        TextInput::make('ward_id')
                                            ->label('Ward')
                                            ->disabled(),
                                        TextInput::make('village')
                                            ->label('Village')
                                            ->disabled(),
                                        TextInput::make('street')
                                            ->label('Street')
                                            ->disabled(),
                                        TextInput::make('landmark')
                                            ->label('Landmark')
                                            ->disabled(),
                                        TextInput::make('latitude')
                                            ->label('Latitude')
                                            ->disabled(),
                                        TextInput::make('longitude')
                                            ->label('Longitude')
                                            ->disabled(),
                                        TextInput::make('building')
                                            ->label('Building')
                                            ->disabled(),
                                        TextInput::make('floor_no')
                                            ->label('Floor No')
                                            ->disabled(),
                                        TextInput::make('house_no')
                                            ->label('House No')
                                            ->disabled(),
                                        TextInput::make('estate')
                                            ->label('Estate')
                                            ->disabled(),
                                        CuratorPicker::make('image')
                                            ->label('Image')
                                            ->disabled(),
                                        TextInput::make('image_description')
                                            ->label('Image Description')
                                            ->disabled(),
                                    ])
                                    ->itemLabel(fn (array $state): ?string => $state['address_type'] ?? null)
                                    ->disabled()
                                    ->collapsible()
                                    ->columns(3),
                            ]),
                        Step::make('Spouse')
                            ->description('Spouse Information')
                            ->schema([
                                TextInput::make('spouse_name')
                                    ->label('Spouse Name')
                                    ->disabled(),
                                TextInput::make('spouse_mobile')
                                    ->label('Spouse Mobile')
                                    ->disabled(),
                                TextInput::make('spouse_occupation')
                                    ->label('Spouse Occupation')
                                    ->disabled(),
                                PdfViewerField::make('consent_form')
                                    ->label('Consent Form'),
                            ])->columns(3),
                        Step::make('Next of Kin')
                            ->description('Next of Kin Information')
                            ->schema([
                                Repeater::make('next_of_kin')
                                    ->schema([
                                        TextInput::make('first_name')
                                            ->label('First Name')
                                            ->disabled(),
                                        TextInput::make('middle_name')
                                            ->label('Middle Name')
                                            ->disabled(),
                                        TextInput::make('last_name')
                                            ->label('Last Name')
                                            ->disabled(),
                                        TextInput::make('relationship')
                                            ->label('Relationship')
                                            ->disabled(),
                                        TextInput::make('mobile')
                                            ->label('Mobile')
                                            ->disabled(),
                                        TextInput::make('email')
                                            ->label('Email')
                                            ->disabled(),
                                        TextInput::make('gender')
                                            ->label('Gender')
                                            ->disabled(),
                                        TextInput::make('address')
                                            ->label('Address')
                                            ->disabled(),
                                    ])
                                    ->columns(2)
                                    ->itemLabel(fn (array $state): ?string => $state['first_name'] ?? null)
                                    ->disabled()
                                    ->collapsible(),
                            ]),
                            Step::make('Referees')
                            ->description('Referees Information')
                            ->schema([
                                Repeater::make('referees')
                                    ->schema([
                                        TextInput::make('name')
                                            ->label('Name')
                                            ->disabled(),
                                        TextInput::make('mobile')
                                            ->label('Mobile')
                                            ->disabled(),
                                        TextInput::make('email')
                                            ->label('Email')
                                            ->disabled(),
                                        TextInput::make('address')
                                            ->label('Address')
                                            ->disabled(),
                                        TextInput::make('relationship')
                                            ->label('Relationship')
                                            ->disabled(),
                                    ])
                                    ->columns(2)
                                    ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
                                    ->disabled(),
                            ]),
                        Step::make('Client Lead Source')
                            ->description('Client Lead Source')
                            ->schema([
                                TextInput::make('lead_source')
                                    ->label('Lead Source')
                                    ->disabled(),
                                TextInput::make('existing_client')
                                    ->label('Existing Client')
                                    ->disabled(),
                            ]),
                        Step::make('Admin')
                            ->description('Admin Approval')
                            ->schema([
                                Checkbox::make('terms_and_condition')
                                ->label('Clients accepts Terms and Conditions?')
                                ->accepted()
                                ->disabled()
                                ->required(),
                                Checkbox::make('privacy_policy')
                                ->label('Clients accepts Privacy Policy?')
                                ->accepted()
                                ->disabled()
                                ->required(),
                                Checkbox::make('signature_confirmed')
                                ->label('Client Signature Confirmed?')
                                ->accepted()
                                ->disabled()
                                ->required(),
                                Checkbox::make('referees_contacted')
                                ->label('Client Referees Contacted?')
                                ->accepted()
                                ->disabled()
                                ->required(),
                                PdfViewerField::make('reg_form')
                                ->label('Registration Form')
                                ->required(),
                                TextInput::make('loan_officer_id')
                                ->label('Relationship Officer')
                                ->disabled(),
                            ]),
                    ]),

                    Action::make('disapprove')
                    ->label('Disapprove')
                    ->icon('heroicon-o-x-circle')
                    ->action(function (Client $record) {
                        $record->changeStatus('pending');
                        Notification::make()
                                    ->success()
                                    ->title('Client Disapproved')
                                    ->body('The client has been disapproved successfully.')
                                    ->send();
                    })
                    ->color('warning')
                    ->requiresConfirmation(),
                    
                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->action(function (Client $record) {
                        $record->changeStatus('rejected');
                        Notification::make()
                                    ->success()
                                    ->title('Client Rejected')
                                    ->body('The client has been rejected successfully.')
                                    ->send();
                    })
                    ->color('danger')
                    ->requiresConfirmation(),
               
             
                ]),
            
        ]);
    }
}
