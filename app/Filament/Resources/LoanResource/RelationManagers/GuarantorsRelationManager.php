<?php

namespace App\Filament\Resources\LoanResource\RelationManagers;


use Filament\Forms;
use Filament\Tables;
use App\Enums\Gender;
use App\Models\Client;
use Filament\Forms\Get;
use Filament\Forms\Set;
use App\Models\Currency;
use Filament\Forms\Form;
use App\Models\Loan\Loan;
use Filament\Tables\Table;
use App\Enums\MaritalStatus;
use Tables\Columns\TextColumn;
use Awcodes\Curator\Models\Media;
use Brick\PhoneNumber\PhoneNumberFormat;
use Cheesegrits\FilamentPhoneNumbers;
use App\Models\ClientRelationship;
use App\Models\Loan\LoanGuarantor;
use Filament\Forms\Components\Grid;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Actions\ActionGroup;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Illuminate\Database\Eloquent\Builder;
use Awcodes\Curator\Components\Forms\CuratorPicker;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Select; // Add this line
use Filament\Resources\RelationManagers\RelationManager;



class GuarantorsRelationManager extends RelationManager
{
    protected static string $relationship = 'guarantors';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('loan_id')
                    ->default($this->getOwnerRecord()->id),
                Forms\Components\Hidden::make('client_id')
                    ->default($this->getOwnerRecord()->client_id),
                    Forms\Components\Select::make('is_previous')
                    ->label('Previous Guarantor?')
                    ->options([
                        '1' => 'Yes',
                        '0' => 'No',
                    ])
                    ->placeholder('Select')
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn (Select $component) => $component
                        ->getContainer()
                        ->getComponent('newGuarantor')
                        ->getChildComponentContainer()
                        ->fill()),

                Grid::make(2)
                        ->schema(fn (Get $get): array => match ($get('is_previous')) {
                            '1' => [
                                Select::make('client')
                                ->label('Client Name')
                                ->searchable()
                                ->placeholder('Select Client')
                                ->options(
                                    LoanGuarantor::where('client_id', $this->getOwnerRecord()->client_id)
                                                ->where('loan_id','!=', $this->getOwnerRecord()->id)
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
                                                                    ->from('loan_guarantors')

                                                                    ->where('loan_id', $this->getOwnerRecord()->id);
                                                            })
                                                            ->where('id', '!=', $this->getOwnerRecord()->client_id) // Exclude current client
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
                                                CuratorPicker::make('photo')
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



            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('loan_id')
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
                     ->label('Full Name'),
                Tables\Columns\TextColumn::make('mobile'),
                Tables\Columns\TextColumn::make('id_number')
                    ->label('ID Number'),
                Tables\Columns\TextColumn::make('guaranteed_amount')
                    ->money(Currency::where('is_default', 1)->first()->symbol),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                // Tables\Actions\AttachAction::make()
                // ->preloadRecordSelect(),
                Tables\Actions\CreateAction::make()
                     ->label('Add Guarantor')
                     ->icon('heroicon-o-plus-circle'),
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make()
                  ->mutateFormDataUsing(function (array $data): array {

                        
                            $loan = Loan::findOrFail($data['loan_id']);
                            if($loan){
                                $data['client_id'] = $loan->client_id;
                            }else{
                                $data['client_id'] = null;
                            }
                        return $data;})
                    ->form([
                      Forms\Components\Hidden::make('loan_id'),
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
                             ->preload()
                            ->searchable(),
                        TextInput::make('mobile')
                            ->required()
                            ->tel()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->email()
                             ->maxLength(255),
                         TextInput::make('id_number')
                               ->numeric()
                               ->required()
                               ->unique(ignoreRecord: true),
                         Textarea::make('address')
                            ->maxLength(65535),
                        TextInput::make('city')
                            ->maxLength(255),
                        TextInput::make('state')
                            ->maxLength(255),
                        CuratorPicker::make('photo')
                                        ,
                        TextInput::make('guaranteed_amount')
                             ->numeric()
                            ->required()
                            ->prefix(Currency::where('is_default', 1)->first()->symbol),

                    ]),
                    Tables\Actions\DeleteAction::make(),
                ])
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public function isReadOnly(): bool
    {
        return false;
    }
}
