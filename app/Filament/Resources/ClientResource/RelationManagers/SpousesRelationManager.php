<?php

namespace App\Filament\Resources\ClientResource\RelationManagers;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Enums\Relationship;
use Cheesegrits\FilamentPhoneNumbers;
use Brick\PhoneNumber\PhoneNumberFormat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Resources\RelationManagers\RelationManager;
use Joaopaulolndev\FilamentPdfViewer\Forms\Components\PdfViewerField;

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
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('relationship')
                    ->options(Relationship::class)
                    ->required(),
                FilamentPhoneNumbers\Forms\Components\PhoneNumber::make('mobile')
                    ->label('Mobile')
                    ->region('KE')
                    ->displayFormat(PhoneNumberFormat::E164)
                    ->mask('9999999999')
                    ->required(),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->maxLength(255),
                Forms\Components\TextInput::make('occupation')
                    ->maxLength(255),
                Forms\Components\Textarea::make('address')
                    ->maxLength(1000),
                PdfViewerField::make('consent_form')
                    ->label('View the PDF')
                    ->minHeight('40svh')
                    ->required(),
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
                Tables\Columns\TextColumn::make('email'),
                Tables\Columns\ImageColumn::make('photo')
                   ->size(40),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Add Spouse')
                    ->icon('heroicon-o-plus-circle'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
