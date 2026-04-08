<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
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
            ->path('admin')
            ->spa()
            ->login(\App\Filament\Pages\Auth\CustomLogin::class)
            ->authGuard('admin')
            ->brandName(\App\Models\SystemSetting::get('app_name', 'Solar BigBang'))
            ->brandLogo(\App\Models\SystemSetting::getUrl('app_logo'))
            ->brandLogoHeight('3rem')
            ->favicon(\App\Models\SystemSetting::getUrl('app_logo'))
            ->colors([
                'primary' => Color::Blue,
            ])
            ->errorNotifications(false)
            ->maxContentWidth(\Filament\Support\Enums\Width::Full)
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                \App\Filament\Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->renderHook(
                PanelsRenderHook::USER_MENU_BEFORE,
                fn (): string => view('filament.hooks.topbar-admin-alerts')->render(),
            )
            ->renderHook(
                PanelsRenderHook::BODY_START,
                fn (): string => view('filament.hooks.page-load-indicator')->render(),
            )
            ->renderHook(
                PanelsRenderHook::TOPBAR_END,
                fn (): string => config('app.filament_show_error_test_button')
                    ? view('filament.hooks.error-test-button')->render()
                    : '',
            )
            ->renderHook(
                PanelsRenderHook::SCRIPTS_AFTER,
                fn (): string => view('filament.hooks.crud-error-snackbar')->render(),
            )
            ->plugins([
                FilamentShieldPlugin::make(),
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
