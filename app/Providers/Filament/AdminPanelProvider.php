<?php

namespace App\Providers\Filament;

use Filament\Pages;
use Filament\Panel;
use Filament\Widgets;
use App\Models\Branch;
use App\Models\Client;
use Filament\PanelProvider;
use App\Filament\Pages\Backups;
use Filament\Navigation\MenuItem;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\MaxWidth;
use Filament\widgets\UserMultiWidget;
use Filament\Navigation\NavigationItem;
use App\Filament\Pages\Settings\Settings;
use Filament\Forms\Components\FileUpload;
use App\Filament\Pages\HealthCheckResults;
use App\Filament\Resources\ClientResource;
use App\Http\Middleware\ApplyTenantScopes;
use Filament\Http\Middleware\Authenticate;
use Jeffgreco13\FilamentBreezy\BreezyCore;
use EightyNine\ExcelImport\ExcelImportAction;
use App\Filament\Pages\Tenancy\RegisterBranch;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Cookie\Middleware\EncryptCookies;
use App\Filament\Pages\Tenancy\EditBranchProfile;
use Awcodes\FilamentStickyHeader\StickyHeaderPlugin;
use DiogoGPinto\AuthUIEnhancer\AuthUIEnhancerPlugin;
use GeoSot\FilamentEnvEditor\FilamentEnvEditorPlugin;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Hugomyb\FilamentErrorMailer\FilamentErrorMailerPlugin;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Njxqlus\FilamentProgressbar\FilamentProgressbarPlugin;
use Leandrocfe\FilamentApexCharts\FilamentApexChartsPlugin;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Croustibat\FilamentJobsMonitor\FilamentJobsMonitorPlugin;
use Tapp\FilamentAuthenticationLog\FilamentAuthenticationLogPlugin;
use Outerweb\FilamentSettings\Filament\Plugins\FilamentSettingsPlugin;
use ShuvroRoy\FilamentSpatieLaravelBackup\FilamentSpatieLaravelBackupPlugin;
use ShuvroRoy\FilamentSpatieLaravelHealth\FilamentSpatieLaravelHealthPlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->spa()
            ->id('admin')
            //->domain(env('APP_SUBDOMAIN'))
            ->path('admin')
            ->login()
            ->brandName(setting('general.brand_name') ?? 'Finta Tech')
            ->brandLogo(env('APP_URL') . '/storage/' . setting('general.brand_logo') ?? '')
            ->brandLogoHeight('4rem')
            ->favicon(env('APP_URL') . '/storage/' . setting('general.favicon') ?? '')
            ->tenant(Branch::class, slugAttribute: 'slug')
            ->tenantRegistration(RegisterBranch::class)
            ->tenantProfile(EditBranchProfile::class)
            ->tenantMenuItems([
                MenuItem::make()
                    ->label('Settings')
                    ->visible(fn (): bool => auth()->user()->isSuperAdmin())

                    ->url(fn (): string => Settings::getUrl())
                    ->icon('heroicon-m-cog-8-tooth')
                    ,
                'register' => MenuItem::make()->label('Register new Branch')
                    ->visible(fn (): bool => auth()->user()->isSuperAdmin()),
                'profile' => MenuItem::make()->label('Edit Branch profile')
                     ->visible(fn (): bool => auth()->user()->isSuperAdmin()),
                // ...
            ])
           ->tenantMiddleware([ApplyTenantScopes::class,], isPersistent: true)
            ->colors([
                'danger' => Color::Rose,
                'gray' => Color::Gray,
                'info' => Color::Blue,
                //'primary' => 'rgb(241,243,206)',
                'primary' => 'rgb(0,41,60)',
                'success' => Color::Emerald,
                'warning' => Color::Orange,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->discoverClusters(in: app_path('Filament/Clusters'), for: 'App\\Filament\\Clusters')
            ->pages([
                //Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                \TomatoPHP\FilamentNotes\Filament\Widgets\NotesWidget::class,

                //Widgets\FilamentInfoWidget::class,
            ])
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->maxContentWidth(MaxWidth::Full)
            ->navigationItems([
                NavigationItem::make('Analytics')
                    ->url('https://filament.pirsch.io', shouldOpenInNewTab: true)
                    ->icon('heroicon-o-presentation-chart-line')
                    ->group('Reports')
                    ->sort(3),
                NavigationItem::make('dashboard')
                    ->label('customer 360 view')
                    ->url(fn (): string => ClientResource::getUrl())
                    ->icon('heroicon-o-user-group')
                    ->sort(1),
                // ...
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->databaseNotifications()
            ->databaseNotificationsPolling('5s')
            ->navigationGroups([
              'Transactions',
                'Accounting',
                'Clients Management',
                'Loans Management',
                'Front Office',
                'Back office',
                'Investors Management',
                'Account Management',
                'Assets Management',
                'Expense Management',
                'Reports',
                'Staff Management',
                'Settings',
            ])
            ->resources([
                config('filament-logger.activity_resource')
            ])
            ->plugins([
                //ReportsPlugin::make(),
                \TomatoPHP\FilamentNotes\FilamentNotesPlugin::make()
                        ->useNotification(),
                FilamentSpatieLaravelBackupPlugin::make()
                    ->usingPage(Backups::class),
                FilamentProgressbarPlugin::make()->color('#29b'),
                FilamentErrorMailerPlugin::make(),
                //FilamentJobsMonitorPlugin::make(),
                // FilamentEnvEditorPlugin::make()
                //     ->navigationGroup('Settings')
                //     ->navigationLabel('My Env')
                //     ->navigationIcon('heroicon-o-cog-8-tooth')
                //     ->navigationSort(1)
                //     ->slug('env-editor'),
                //FilamentAuthenticationLogPlugin::make(),
                \BezhanSalleh\FilamentShield\FilamentShieldPlugin::make()
                                                    ->gridColumns([
                                                        'default' => 1,
                                                        'sm' => 2,
                                                        'lg' => 3
                                                    ])
                                                    ->sectionColumnSpan(1)
                                                    ->checkboxListColumns([
                                                        'default' => 1,
                                                        'sm' => 2,
                                                        'lg' => 2,
                                                    ])
                                                    ->resourceCheckboxListColumns([
                                                        'default' => 1,
                                                        'sm' => 2,
                                                    ]),
                \BezhanSalleh\FilamentExceptions\FilamentExceptionsPlugin::make(),

                FilamentApexChartsPlugin::make(),
              
                 BreezyCore::make()
                        ->myProfile(
                            shouldRegisterUserMenu: true, // Sets the 'account' link in the panel User Menu (default = true)
                            shouldRegisterNavigation: false, // Adds a main navigation item for the My Profile page (default = false)
                            hasAvatars: true, // Enables the avatar upload form component (default = false)
                            slug: 'my-profile' // Sets the slug for the profile page (default = 'my-profile')
                    )
                    ->avatarUploadComponent(fn() => FileUpload::make('avatar_url')
                                                                ->image()
                                                                ->avatar()
                                                                ->imageEditor()
                                                                ->circleCropper())
                    ->enableSanctumTokens(
                        permissions: ['my','custom','permissions'] // optional, customize the permissions (default = ["create", "view", "update", "delete"])
                    ),
                    FilamentSpatieLaravelHealthPlugin::make()
                        ->usingPage(HealthCheckResults::class),
                    FilamentSettingsPlugin::make()
                                ->pages([
                                    \App\Filament\Pages\Settings\Settings::class,
                                    //app\Filament\Pages\Settings\Settings::class,
                                ]),
                    \Awcodes\Curator\CuratorPlugin::make()
                                ->label('Media')
                                ->pluralLabel('Media')
                                ->navigationIcon('heroicon-o-photo')
                                ->navigationGroup('Media Management')
                                ->navigationCountBadge(),
                    StickyHeaderPlugin::make()
                                ->floating(),
                                //->colored(),
                    AuthUIEnhancerPlugin::make()
                        //->showEmptyPanelOnMobile(false)
                        ->mobileFormPanelPosition('top')
                        ->formPanelPosition('left')
                        ->formPanelWidth('40%')
                        ->emptyPanelBackgroundImageOpacity('70%')
                        ->emptyPanelBackgroundImageUrl('https://images.pexels.com/photos/466685/pexels-photo-466685.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=2'),            

            ]);
    }
}
