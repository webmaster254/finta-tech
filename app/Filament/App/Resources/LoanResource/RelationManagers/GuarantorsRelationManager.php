<?php

namespace App\Filament\App\Resources\LoanResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Awcodes\Curator\Models\Media;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class GuarantorsRelationManager extends RelationManager
{
    protected static string $relationship = 'guarantors';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('loan_id')
                    ->required()
                    ->maxLength(255),
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
                    ->label('ID NUmber'),
                Tables\Columns\TextColumn::make('guaranteed_amount')
                    ->money('KES'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
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
}
