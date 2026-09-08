<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Navigation\NavigationItem;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('/')
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->login(\App\Filament\Pages\Auth\Login::class)
            ->profile(\App\Filament\Pages\EditProfile::class, isSimple: false)
            ->colors([
                'primary' => Color::Amber,
                'gray' => Color::Slate,
            ])
            ->font('Plus Jakarta Sans')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->navigationItems([
                NavigationItem::make('Profil Saya')
                    ->url(fn (): string => \App\Filament\Pages\EditProfile::getUrl())
                    ->icon('heroicon-o-user')
                    ->isActiveWhen(fn (): bool => request()->routeIs('filament.admin.auth.profile'))
                    ->sort(-1),
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
                \App\Filament\Widgets\SaldoCutiWidget::class,
                \App\Filament\Widgets\StatistikCutiWidget::class,
                \App\Filament\Widgets\MenungguKeputusanWidget::class,
                \App\Filament\Widgets\PegawaiSedangCutiWidget::class,
                \App\Filament\Widgets\RiwayatPengajuanWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                \App\Http\Middleware\ReferrerPolicyNoReferrer::class,
            ])
            ->authMiddleware([
                \App\Http\Middleware\TabContextMiddleware::class,
                \App\Http\Middleware\TabAuthMiddleware::class,
                \App\Http\Middleware\EnforceProfileCompletion::class,
            ])
            ->authGuard('tab')
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
            ->globalSearch(false)
            ->renderHook(
                \Filament\View\PanelsRenderHook::SCRIPTS_AFTER,
                fn () => view('components.tab-session-script')
            );
    }
}
