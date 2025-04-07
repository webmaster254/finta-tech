<?php

namespace App\Filament\Pages;

use App\Models\Client;
use Filament\Pages\Page;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Actions\Action;

class ApproveClient extends Page implements HasTable
{

    use InteractsWithTable;
  
    protected static ?string $navigationLabel = 'Approve Clients';
    protected static ?string $navigationGroup = 'Clients Management';
    protected static ?int $navigationSort = 1;


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
                ->label('Status'),
            ])
            
            ->actions([
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check')
                    ->action(function (Client $record) {
                        $record->status = 'approved';
                        $record->save();
                    }),
                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->action(function (Client $record) {
                        $record->status = 'rejected';
                        $record->save();
                    }),
            ]);
           
    }
}
