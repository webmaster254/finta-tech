<?php

namespace App\Filament\Pages;

use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Livewire\Attributes\Url;
use App\Models\ChartOfAccount;
use Filament\Actions\EditAction;
use Livewire\Attributes\Computed;
use Filament\Actions\CreateAction;
use Illuminate\Support\Collection;
use App\Enums\ChartAccountCategory;
use Filament\Support\Enums\MaxWidth;
use App\Models\ChartOfAccountSubtype;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use App\Utilities\Accounting\AccountCode;
use App\Utilities\Currency\CurrencyAccessor;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;

class AccountChart extends Page
{

    use HasPageShield;
    protected static ?string $title = 'Chart of Accounts';
    protected static ?string $navigationIcon = null;
    protected static ?string $navigationGroup = 'Accounting';

    protected static string $view = 'filament.pages.chart-of-account';

    #[Url]
    public ?string $activeTab = null;

    public function mount(): void
    {
        $this->activeTab = $this->activeTab ?? ChartAccountCategory::Asset->value;
    }

    protected function configureAction(Action $action): void
    {
        $action
            ->modal()
            ->modalWidth(MaxWidth::TwoExtraLarge);
    }

    #[Computed]
    public function categories(): Collection
    {
        return ChartOfAccountSubtype::withCount('accounts')
            ->get()
            ->groupBy('category');
    }

    public function editChartAction(): Action
    {
        return EditAction::make()
            ->iconButton()
            ->name('editChart')
            ->label('Edit account')
            ->modalHeading('Edit Account')
            ->icon('heroicon-m-pencil-square')
            ->record(fn (array $arguments) => ChartOfAccount::find($arguments['chart']))
            ->form(fn (Form $form) => $this->getChartForm($form)->operation('edit'));
    }

    public function createChartAction(): Action
    {
        return CreateAction::make()
            ->link()
            ->name('createChart')
            ->model(ChartOfAccount::class)
            ->label('Add a new account')
            ->icon('heroicon-o-plus-circle')
            ->form(fn (Form $form) => $this->getChartForm($form)->operation('create'))
            ->fillForm(fn (array $arguments): array => $this->getChartFormDefaults($arguments['subtype']));
    }

    private function getChartFormDefaults(int $subtypeId): array
    {
        $accountSubtype = ChartOfAccountSubtype::find($subtypeId);
        $generatedCode = AccountCode::generate($accountSubtype);

        return [
            'subtype_id' => $subtypeId,
            'gl_code' => $generatedCode,
        ];
    }

    private function getChartForm(Form $form, bool $useActiveTab = true): Form
    {
        return $form
            ->schema([
                $this->getTypeFormComponent($useActiveTab),
                $this->getCodeFormComponent(),
                $this->getNameFormComponent(),
                 $this->getCurrencyFormComponent(),
                $this->getDescriptionFormComponent(),
            ]);
    }

    protected function getTypeFormComponent(bool $useActiveTab = true): Component
    {
        return Select::make('subtype_id')
            ->label('Type')
            ->required()
            ->live()
            ->disabled(static function (string $operation): bool {
                return $operation === 'edit';
            })
            ->options($this->getChartSubtypeOptions($useActiveTab))
            ->afterStateUpdated(static function (?string $state, Set $set): void {
                if ($state) {
                    $accountSubtype = ChartOfAccountSubtype::find($state);
                    $generatedCode = AccountCode::generate($accountSubtype);
                    $set('gl_code', $generatedCode);
                }
            });
    }

    protected function getCodeFormComponent(): Component
    {
        return TextInput::make('gl_code')
            ->label('Code')
            ->required()
            ->validationAttribute('account code')
            ->unique(table: ChartOfAccount::class, column: 'gl_code', ignoreRecord: true)
            ->validateAccountCode(static fn (Get $get) => $get('subtype_id'));
    }

    protected function getNameFormComponent(): Component
    {
        return TextInput::make('name')
            ->label('Name')
            ->required();
    }

    protected function getCurrencyFormComponent()
    {
        return Select::make('currency_code')
            // ->localizeLabel('Currency')
            ->relationship('currency', 'name')
            ->default(CurrencyAccessor::getDefaultCurrency())
            ->preload()
            ->searchable()
            ->disabled(static function (string $operation): bool {
                return $operation === 'edit';
            })
            ->visible(function (Get $get): bool {
                return filled($get('subtype_id')) && ChartOfAccountSubtype::find($get('subtype_id'));
            })
            ->live();
    }

    protected function getDescriptionFormComponent(): Component
    {
        return Textarea::make('description')
            ->label('Description')
            ->autosize();
    }

    private function getChartSubtypeOptions($useActiveTab = true): array
    {
        $subtypes = $useActiveTab ?
            ChartOfAccountSubtype::where('category', $this->activeTab)->get() :
            ChartOfAccountSubtype::all();

        return $subtypes->groupBy(fn (ChartOfAccountSubtype $subtype) => $subtype->type->getLabel())
            ->map(fn (Collection $subtypes, string $type) => $subtypes->mapWithKeys(static fn (ChartOfAccountSubtype $subtype) => [$subtype->id => $subtype->name]))
            ->toArray();
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->button()
                ->label('Add New Account')
                ->model(ChartOfAccount::class)
                ->form(fn (Form $form) => $this->getChartForm($form, false)->operation('create')),
        ];
    }

    public function getCategoryLabel($categoryValue): string
    {
        return ChartAccountCategory::from($categoryValue)->getPluralLabel();
    }
}
