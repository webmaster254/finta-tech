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
use Filament\Pages\Actions\CreateAction;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\ImageColumn;

use Filament\Forms\Components\FileUpload;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\ClientResource;
use Filament\Forms\Components\Wizard\Step;
use App\Jobs\SendRegistrationNotificationJob;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Tables\Concerns\InteractsWithTable;
use Awcodes\Curator\Components\Forms\CuratorPicker;
use Joaopaulolndev\FilamentPdfViewer\Forms\Components\PdfViewerField;

class ListSubmittedClients extends Page implements HasTable
{
    protected static ?string $model = Client::class;

    protected static string $view = 'filament.pages.list-submitted-clients';

    protected static ?string $navigationLabel = 'Client Maintenance';
    protected static ?string $navigationGroup = 'Clients Management';
    protected  ?string $heading = 'Client Maintenance';
    protected static ?int $navigationSort = 1; 
    
    use InteractsWithTable;

    public static function getNavigationBadge(): ?string
    {
        return Client::where('status', 'rts')->count();
    }

     protected function getHeaderActions(): array
    {
        return [
            
            CreateAction::make()
                ->icon("heroicon-o-plus")
                ->label('Create Client')
                ->url(fn (): string => ClientResource::getUrl('create')),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
        ->query(Client::query()->where('status', 'rts'))
            ->columns([
                TextColumn::make('full_name'),
                TextColumn::make('mobile'),
                TextColumn::make('loan_officer.fullname')
                ->label('Relationship Officer'),
                TextColumn::make('rts_remarks')
                ->label('RTS Remarks'),
                TextColumn::make('status')
                ->badge()
                ->label('Status'),
            ])
            
            ->actions([
                ActionGroup::make([
                    Action::make('edit')
                        ->label('Edit Client')
                        ->icon('heroicon-o-pencil')
                        ->url(fn (Client $record): string => ClientResource::getUrl('edit', ['record' => $record]))
                        
                        ->color('info'),
                    Action::make('Submit')
                        ->label('Submit')
                        ->icon('heroicon-o-check')
                        ->action(function (Client $record) {
                            $record->changeStatus('pending');
                            Notification::make()
                                        ->success()
                                        ->title('Client Submitted')
                                        ->body('The client has been submitted successfully.')
                                        ->send();
                        })
                        ->color('success')
                        ->requiresConfirmation(),
                ])
            ]);
    }
}
