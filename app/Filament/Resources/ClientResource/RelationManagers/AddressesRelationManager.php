<?php

namespace App\Filament\Resources\ClientResource\RelationManagers;

use Filament\Forms;
use App\Models\Town;
use Filament\Tables;
use App\Models\County;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Form;
use App\Models\SubCounty;
use Filament\Tables\Table;
use Filament\Infolists\Infolist;
use Dotswan\MapPicker\Fields\Map;
use Infolists\Components\TextEntry;
use Dotswan\MapPicker\Infolists\MapEntry;
use Filament\Forms\Components\FileUpload;
use Illuminate\Database\Eloquent\Builder;
use Awcodes\Curator\Components\Forms\CuratorPicker;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Awcodes\Curator\Components\Tables\CuratorColumn;
use Awcodes\Curator\PathGenerators\DatePathGenerator;
use Cheesegrits\FilamentGoogleMaps\Columns\MapColumn;
use Filament\Resources\RelationManagers\RelationManager;
use Parfaitementweb\FilamentCountryField\Forms\Components\Country;
use Parfaitementweb\FilamentCountryField\Tables\Columns\CountryColumn;

class AddressesRelationManager extends RelationManager
{
    protected static string $relationship = 'addresses';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                
                Forms\Components\Hidden::make('client_id')
                    ->default($this->getOwnerRecord()->id),
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
        
        Geocomplete::make('location')
            ->label('Location')
            ->geolocate()
            ->updateLatLng()
            ->isLocation()
            ->geocodeOnLoad()
            ->prefix('Choose:')
            ->required(),
        Forms\Components\TextInput::make('latitude')
        ->readOnly(),
        Forms\Components\TextInput::make('longitude')
        ->readOnly(),
        Map::make('location')
            ->reactive()
            ->afterStateUpdated(function ($state, callable $get, callable $set) {
                $set('latitude', $state['lat']);
                $set('longitude', $state['lng']);
            }),
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
              
                
           
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('address_type'),
                CountryColumn::make('country')->label('Country'),
                Tables\Columns\TextColumn::make('county.name')->label('County'),
                Tables\Columns\TextColumn::make('subCounty.name')->label('Sub County'),
                Tables\Columns\TextColumn::make('ward.name')->label('Ward'),
                Tables\Columns\TextColumn::make('street'),
                Tables\Columns\TextColumn::make('village'),
                Tables\Columns\TextColumn::make('landmark'),
                Tables\Columns\TextColumn::make('building'),
                Tables\Columns\TextColumn::make('floor_no'),
                Tables\Columns\TextColumn::make('house_no'),
                Tables\Columns\TextColumn::make('estate'),
                MapColumn::make('location')
                        ->extraAttributes([
                        'class' => 'my-funky-class'
                        ]) // Optionally set any additional attributes, merged into the wrapper div around the image tag
                        ->extraImgAttributes(
                            fn ($record): array => ['title' => $record->latitude . ',' . $record->longitude]
                        ) // Optionally set any additional attributes you want on the img tag
                        ->height('150') // API setting for map height in PX
                        ->width('250') // API setting got map width in PX
                        ->type('hybrid') // API setting for map type (hybrid, satellite, roadmap, tarrain)
                        ->zoom(15) // API setting for zoom (1 through 20)
                        ->ttl(60 * 60 * 24 * 30), // number of seconds to cache image before refetching from API
                Tables\Columns\TextColumn::make('latitude'),
                Tables\Columns\TextColumn::make('longitude'),
                Tables\Columns\ImageColumn::make('image'),
                Tables\Columns\TextColumn::make('image_description'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                     ->label('Add Address')
                     ->icon('heroicon-o-plus-circle'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalHeading('View Address')
                    ->modalDescription('View address information')
                    ->modalIcon('heroicon-o-building-office-2')
                    ->modalIconColor('success'),
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
