<?php

namespace App\Filament\Resources\ClientResource\RelationManagers;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Get;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Enums\Relationship;
use Illuminate\Support\HtmlString;
use Carbon\Carbon;
use Cheesegrits\FilamentPhoneNumbers;
use Brick\PhoneNumber\PhoneNumberFormat;
use Filament\Forms\Components\FileUpload;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Resources\RelationManagers\RelationManager;
use Saade\FilamentAutograph\Forms\Components\SignaturePad;
use Joaopaulolndev\FilamentPdfViewer\Forms\Components\PdfViewerField;
use Saade\FilamentAutograph\Forms\Components\Enums\DownloadableFormat;

class SpousesRelationManager extends RelationManager
{
    protected static string $relationship = 'spouse';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('client_id')
                    ->default($this->getOwnerRecord()->id),
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
                FileUpload::make('photo')
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
                FileUpload::make('consent_signature_upload')
                        ->label('Upload Consent Signature')
                        ->image()
                        ->imageEditor()
                        ->required()
                        ->loadingIndicatorPosition('left')
                        ->panelAspectRatio('2:1')
                        ->panelLayout('integrated')
                        ->removeUploadedFileButtonPosition('right')
                        ->uploadButtonPosition('left')
                        ->uploadProgressIndicatorPosition('left'),
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
                   ->velocityFilterWeight(0.7) 
                    ->downloadable()                    // Allow download of the signature (defaults to false)
                   ->downloadableFormats([             // Available formats for download (defaults to all)
                       DownloadableFormat::PNG,
                       DownloadableFormat::JPG,
                       DownloadableFormat::SVG,
                   ]),
            ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('id_number'),
                Tables\Columns\TextColumn::make('mobile'),
                Tables\Columns\ImageColumn::make('photo')
                   ->size(40),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                // Tables\Actions\CreateAction::make()
                //     ->label('Add Spouse')
                //     ->icon('heroicon-o-plus-circle'),
            ])
            ->actions([
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
