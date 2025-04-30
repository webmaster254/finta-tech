<?php

namespace App\Filament\Pages;

use Carbon\Carbon;
use App\Models\Client;
use Filament\Forms\Get;
use Filament\Pages\Page;
use Filament\Tables\Table;
use Filament\Facades\Filament;
use Illuminate\Support\HtmlString;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\ImageColumn;
use Filament\Forms\Components\FileUpload;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Wizard\Step;
use App\Jobs\SendRegistrationNotificationJob;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Tables\Concerns\InteractsWithTable;
use Awcodes\Curator\Components\Forms\CuratorPicker;
use Saade\FilamentAutograph\Forms\Components\SignaturePad;
use Joaopaulolndev\FilamentPdfViewer\Forms\Components\PdfViewerField;
use Saade\FilamentAutograph\Forms\Components\Enums\DownloadableFormat;

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
                        // Check source of income and validate required information
                        $sourceOfIncome = $record->source_of_income;
                        $recipient = auth()->user();
                        // Check if business information is required and filled
                        if ($sourceOfIncome === 'Business' && !$record->business()->exists()) {
                            Notification::make()
                                ->danger()
                                ->title('Missing Business Information')
                                ->body('Business information must be filled before approving a client with business income source.')
                                ->sendToDatabase($recipient)
                                ->send();
                            return;
                        }
                        
                        // Check if employment information is required and filled
                        if ($sourceOfIncome === 'Employed' && !$record->employment()->exists()) {
                            Notification::make()
                                ->danger()
                                ->title('Missing Employment Information')
                                ->body('Employment information must be filled before approving a client with employment income source.')
                                ->sendToDatabase($recipient)
                                ->send();
                            return;
                        }
                        
                        // If validation passes, proceed with approval
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
                        'aka' => $record->aka,
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
                        'consent_form' => $record->spouse->consent_form ?? null,
                        'lead_source' => $record->lead_source ?? null,
                        'existing_client' => $record->existing_client ?? null,
                        'id_verified' => $record->id_verified ?? null,
                        'address_verified' => $record->address_verified ?? null,
                        'signature_confirmed' => $record->signature_confirmed ?? null,
                        'referees_contacted' => $record->referees_contacted ?? null,
                        'privacy_signature' => $record->privacy_signature ?? null,
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
                        'spouse' => $record->spouse ? [
                            'name' => $record->spouse->name ?? null,
                            'id_number' => $record->spouse->id_number ?? null,
                            'mobile' => $record->spouse->mobile ?? null,
                            'occupation' => $record->spouse->occupation ?? null,
                            'id_front' => $record->spouse->id_front ?? null,
                            'id_back' => $record->spouse->id_back ?? null,
                            'photo' => $record->spouse->photo ?? null,
                            'consent_signature' => $record->spouse->consent_signature ?? null,
                        ] : [],
                        'referees' => $record->referees && $record->referees->count() > 0 ? $record->referees->map(function($ref) {
                            return [
                                'name' => $ref->name ?? null,
                                'mobile' => $ref->mobile ?? null,
                                'email' => $ref->email ?? null,
                                'address' => $ref->address ?? null,
                                'relationship' => $ref->client_relationship->name ?? null,
                            ];
                        })->toArray() : [],
                        'client_lead' => $record->client_lead && $record->client_lead->count() > 0 ? $record->client_lead->map(function($lead) {
                            return [
                                'lead_source' => $lead->lead_source ?? null,
                                'existing_client' => isset($lead->existing_client) ? 
                                    (Client::find($lead->existing_client) ? 
                                        Client::find($lead->existing_client)->full_name : 
                                        $record->client_lead['existing_client']) : 
                                    null,
                                'status' => isset($lead->existing_client) ? 
                                    (Client::find($lead->existing_client) ? 
                                        Client::find($lead->existing_client)->status : 
                                        $record->client_lead['status']) : 
                                    null,
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
                                SignaturePad::make('signature')
                                    ->label('Signature')
                                    ->disabled(),
                                FileUpload::make('id_front')
                                    ->label('ID Front')
                                    ->disabled(),
                                FileUpload::make('id_back')
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
                                        FileUpload::make('image')
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
                            ->visible(fn (Client $record) => $record->marital_status === 'married')
                            ->schema([
                                TextInput::make('spouse.name')
                                    ->label('Spouse Name')
                                    ->disabled(),
                                TextInput::make('spouse.mobile')
                                    ->label('Spouse Mobile')
                                    ->disabled(),
                                TextInput::make('spouse.occupation')
                                    ->label('Spouse Occupation')
                                    ->disabled(),
                                TextInput::make('spouse.id_number')
                                    ->label('Spouse ID Number')
                                    ->disabled(),
                                FileUpload::make('spouse.id_front')
                                    ->label('Spouse ID Front')
                                    ->image()
                                    ->imageEditor()
                                    ->imagePreviewHeight('250')
                                    ->disabled(),
                                FileUpload::make('spouse.id_back')
                                    ->label('Spouse ID Back')
                                    ->image()
                                    ->imageEditor()
                                    ->imagePreviewHeight('250')
                                    ->disabled(),
                                FileUpload::make('spouse.photo')
                                    ->label('Spouse Photo')
                                    ->image()
                                    ->imageEditor()
                                    ->imagePreviewHeight('250')
                                    ->disabled(),
                                Placeholder::make('spouse.consent_notes')
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
                                SignaturePad::make('spouse.consent_signature')
                                    ->label('Spouse Consent Signature')
                                    ->disabled(),
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
                                Repeater::make('client_lead')
                                ->columns(3)
                                ->schema([
                                    TextInput::make('lead_source')
                                        ->label('Lead Source')
                                        ->disabled(),
                                    TextInput::make('existing_client')
                                        ->label('Existing Client')
                                        ->visible(fn (\Filament\Forms\Get $get): bool => $get('lead_source') === 'existing_client')
                                        ->disabled(),
                                    Hidden::make('status')
                                        ->label('Status')
                                        ->disabled(),
                                    Placeholder::make('client_status')
                                        ->label('Client Status')
                                        
                                        ->visible(fn (\Filament\Forms\Get $get): bool => $get('lead_source') === 'existing_client')
                                        ->content(function (\Filament\Forms\Get $get) {
                                            return new \Illuminate\Support\HtmlString(
                                                view('filament.components.status-badge', [
                                                    'status' =>  $get('status'),
                                                ])->render()
                                            );
                                        }),
                                    TextInput::make('others')
                                    ->label('Others')
                                    ->visible(fn (\Filament\Forms\Get $get): bool => $get('lead_source') === 'other')
                                    ->disabled(),
                                ])->itemLabel(fn (array $state): ?string => $state['lead_source'] ?? null)
                                ->disabled()
                                
                            ]),
                        Step::make('Privacy')
                            ->description('Privacy Consent')
                            ->schema([
                                Placeholder::make('privacy_policy')
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
                                Placeholder::make('personal_info')
                                    ->label(fn(Get $get) => new HtmlString('
                                    <p style="margin: 15px; color:#00293C; font-weight: bold">Disclosure of personal information and Confidentiality</p>
                                    <p>Finta Tech Limited will not sell, distribute or lease a member\'s personal information to third parties unless the Finta Tech Limited believes it necessary for the
                                        conduct of its business, or are required by law to do so. Except in those specific, limited situations, Finta Tech Limited will not make any disclosures of
                                        non-public personal information without a member\'s consent.</p>'))
                                    ->columnSpanFull()
                                    ->content(''),
                                Placeholder::make('terms')
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
                                Placeholder::make('consent')
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
                                ]),
                            
                        Step::make('Admin')
                            ->description('Admin Approval')
                            ->schema([
                                Toggle::make('id_verified')
                                ->label('ID Verified?')
                                ->accepted()
                                ->disabled()
                                ->required(),
                                Toggle::make('address_verified')
                                ->label('Address Confirmed?')
                                ->accepted()
                                ->disabled()
                                ->required(),
                                Toggle::make('signature_confirmed')
                                ->label('Client Signature Verified?')
                                ->accepted()
                                ->disabled()
                                ->required(),
                                Toggle::make('referees_contacted')
                                ->label('Client Referees Contacted?')
                                ->accepted()
                                ->disabled()
                                ->required(),
                                TextInput::make('loan_officer_id')
                                ->label('Relationship Officer')
                                ->disabled(),
                            ]),
                    ]),

                    Action::make('rts')
                    ->label('RTS')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->form([
                        Textarea::make('rts_remarks')
                            ->label('Remarks')
                            ->required(),
                    ])
                    ->action(function (Client $record, array $data) {
                        $record->changeStatus('rts');
                       $record->update([
                            'rts_remarks' => $data['rts_remarks']
                        ]);
                        Notification::make()
                                    ->success()
                                    ->title('Client RTS')
                                    ->body('The client has been RTS successfully.')
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
