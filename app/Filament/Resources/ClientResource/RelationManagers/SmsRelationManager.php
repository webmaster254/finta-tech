<?php

namespace App\Filament\Resources\ClientResource\RelationManagers;

use Filament\Forms;
use Filament\Tables;
use App\Models\Client;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Services\SmsService;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Resources\RelationManagers\RelationManager;

class SmsRelationManager extends RelationManager
{
    protected static string $relationship = 'sms';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('phone_number')
                    ->required(),
                Forms\Components\TextInput::make('message_description')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Hidden::make('cost')
                    ->default(10)
                    ->required(),
                Forms\Components\Hidden::make('sent_by')
                    ->default(auth()->id())
                    ->required(),
                Forms\Components\Hidden::make('date_sent')
                   ->default(now())
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->heading('Send SMS')
            ->columns([
                Tables\Columns\TextColumn::make('id'),
                Tables\Columns\TextColumn::make('message_description'),
                Tables\Columns\TextColumn::make('cost'),
                Tables\Columns\TextColumn::make('sent_by.full_name'),
                Tables\Columns\TextColumn::make('date_sent'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Action::make('send_sms')
                ->label('Send SMS')
                ->icon('heroicon-o-envelope')
                ->modalHeading('Send SMS')
                ->form([
                        Forms\Components\Textarea::make('message_description')
                        ->required()
                        ->maxLength(255),
                ])
                ->action(function (array $data) {
                    //send sms
                    $message = $data['message_description'];
                    $phone_number = $this->getOwnerRecord()->mobile;
                    $sent_by = auth()->id();
                    $date_sent = now();
                    $cost = 10;

                    try {
                        $response = SmsService::sendSms($phone_number, $message);
                        
                        // The SmsService now returns a properly formatted array
                        if (isset($response['status']) && $response['status'] === 'success') {
                            // Save SMS to database if sent successfully
                            $this->getOwnerRecord()->sms()->create([
                                'message_description' => $message,
                                'cost' => $cost,
                                'sent_by' => $sent_by,
                                'date_sent' => $date_sent,
                            ]);
                            
                            Notification::make()
                                ->title('SMS sent successfully')
                                ->icon('heroicon-o-check')
                                ->success()
                                ->send();
                        } else {
                            // Get error message from response if available
                            $errorMessage = isset($response['message']) ? $response['message'] : 'Unknown error occurred';
                            
                            Notification::make()
                                ->title('Failed to send SMS')
                                ->body($errorMessage)
                                ->icon('heroicon-o-x-mark')
                                ->danger()
                                ->send();
                        }
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Failed to send SMS')
                            ->body($e->getMessage())
                            ->icon('heroicon-o-x-mark')
                            ->danger()
                            ->send();
                    }
                }),
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
