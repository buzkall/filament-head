<?php

namespace Arzcode\FilamentHead\Tests;

use Arzcode\FilamentHead\FilamentHeadServiceProvider;
use Arzcode\FilamentHead\Tests\Fixtures\Models\Post;
use Arzcode\FilamentHead\Tests\Fixtures\Models\User;
use Arzcode\FilamentHead\Tests\Fixtures\Panels\AdminPanelProvider;
use Arzcode\FilamentHead\Tests\Fixtures\Resources\PostResource;
use BladeUI\Heroicons\BladeHeroiconsServiceProvider;
use BladeUI\Icons\BladeIconsServiceProvider;
use Closure;
use Filament\Actions\ActionsServiceProvider;
use Filament\FilamentServiceProvider;
use Filament\Forms\FormsServiceProvider;
use Filament\Infolists\InfolistsServiceProvider;
use Filament\Notifications\NotificationsServiceProvider;
use Filament\Schemas\SchemasServiceProvider;
use Filament\Support\SupportServiceProvider;
use Filament\Tables\TablesServiceProvider;
use Filament\Widgets\WidgetsServiceProvider;
use Illuminate\Support\Facades\Route;
use Laravel\Head\HeadServiceProvider;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use RyanChandler\BladeCaptureDirective\BladeCaptureDirectiveServiceProvider;

abstract class TestCase extends Orchestra
{
    /**
     * Extra config applied while the application boots.
     *
     * @var array<string, mixed>
     */
    public static array $config = [];

    protected function tearDown(): void
    {
        static::$config = [];
        AdminPanelProvider::$configurePlugin = null;
        PostResource::$configureFields = null;

        parent::tearDown();
    }

    /**
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            BladeCaptureDirectiveServiceProvider::class,
            BladeHeroiconsServiceProvider::class,
            BladeIconsServiceProvider::class,
            SupportServiceProvider::class,
            ActionsServiceProvider::class,
            FormsServiceProvider::class,
            InfolistsServiceProvider::class,
            NotificationsServiceProvider::class,
            SchemasServiceProvider::class,
            TablesServiceProvider::class,
            WidgetsServiceProvider::class,
            // Livewire must register after Filament's support provider, which rebinds
            // the Livewire DataStore; otherwise the store is never shared.
            LivewireServiceProvider::class,
            FilamentServiceProvider::class,
            HeadServiceProvider::class,
            FilamentHeadServiceProvider::class,
            AdminPanelProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        $app['config']->set('database.default', 'testing');
        $app['config']->set('auth.providers.users.model', User::class);
        $app['config']->set('cache.default', 'array');
        $app['config']->set('view.paths', [__DIR__.'/Fixtures/views', resource_path('views')]);
        $app['config']->set('filesystems.disks.public', [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => 'http://localhost/storage',
            'visibility' => 'public',
        ]);

        foreach (static::$config as $key => $value) {
            $app['config']->set($key, $value);
        }
    }

    protected function defineRoutes($router): void
    {
        // The public-site half of the package: one call, then a layout with @head.
        Route::get('/posts/{post}', function (Post $post) {
            $post->applyHead();

            return view('layout');
        })->middleware('web');
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->runPackageMigrations();
    }

    protected function runPackageMigrations(): void
    {
        (include __DIR__.'/Fixtures/database/migrations/0001_01_01_000000_create_users_table.php')->up();
        (include __DIR__.'/Fixtures/database/migrations/0001_01_01_000001_create_posts_table.php')->up();
        (include __DIR__.'/../database/migrations/create_head_metadata_table.php.stub')->up();
    }

    /**
     * Rebuilds the application so panel and config changes can differ per test.
     *
     * @param  array<string, mixed>  $config
     */
    protected function rebootWith(array $config = [], ?Closure $configurePlugin = null, ?Closure $configureFields = null): void
    {
        static::$config = [...static::$config, ...$config];

        if ($configurePlugin !== null) {
            AdminPanelProvider::$configurePlugin = $configurePlugin;
        }

        if ($configureFields !== null) {
            PostResource::$configureFields = $configureFields;
        }

        $this->refreshApplication();
        $this->runPackageMigrations();
    }

    protected function getApplicationTimezone($app): string
    {
        return 'UTC';
    }
}
