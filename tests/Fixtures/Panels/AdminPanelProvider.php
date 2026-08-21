<?php

namespace Arzcode\FilamentHead\Tests\Fixtures\Panels;

use Arzcode\FilamentHead\FilamentHeadPlugin;
use Arzcode\FilamentHead\Tests\Fixtures\Resources\PostResource;
use Closure;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    /**
     * Set to register FilamentHeadPlugin on the panel, configured by the closure.
     */
    public static ?Closure $configurePlugin = null;

    public function panel(Panel $panel): Panel
    {
        $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->authGuard('web')
            ->middleware($this->defaultMiddleware())
            ->pages([Dashboard::class])
            ->resources([PostResource::class]);

        if (static::$configurePlugin !== null) {
            $panel->plugin((static::$configurePlugin)(FilamentHeadPlugin::make()));
        }

        return $panel;
    }

    /**
     * Filament's own stack, minus CSRF: the tests only issue GET and Livewire calls.
     *
     * @return array<int, class-string>
     */
    protected function defaultMiddleware(): array
    {
        return [
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            StartSession::class,
            AuthenticateSession::class,
            ShareErrorsFromSession::class,
            SubstituteBindings::class,
            DisableBladeIconComponents::class,
            DispatchServingFilamentEvent::class,
        ];
    }
}
