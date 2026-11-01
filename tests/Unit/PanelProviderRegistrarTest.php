<?php

use Arzcode\FilamentHead\Support\PanelProviderRegistrar;

function panelProviderFile(string $contents): string
{
    $path = tempnam(sys_get_temp_dir(), 'panel').'.php';
    file_put_contents($path, $contents);

    return $path;
}

it('adds the plugin before the panel id and imports it', function (): void {
    $path = panelProviderFile(<<<'PHP'
        <?php

        namespace App\Providers\Filament;

        use Filament\Panel;
        use Filament\PanelProvider;

        class AdminPanelProvider extends PanelProvider
        {
            public function panel(Panel $panel): Panel
            {
                return $panel
                    ->default()
                    ->id('admin')
                    ->path('admin');
            }
        }
        PHP);

    expect(new PanelProviderRegistrar(app())->register($path))->toBeTrue()
        ->and(file_get_contents($path))->toBe(<<<'PHP'
            <?php

            namespace App\Providers\Filament;

            use Filament\Panel;
            use Filament\PanelProvider;
            use Arzcode\FilamentHead\FilamentHeadPlugin;

            class AdminPanelProvider extends PanelProvider
            {
                public function panel(Panel $panel): Panel
                {
                    return $panel
                        ->default()
                        ->plugin(FilamentHeadPlugin::make())
                        ->id('admin')
                        ->path('admin');
                }
            }
            PHP);

    unlink($path);
});

it('does not import the plugin twice', function (): void {
    $path = panelProviderFile(<<<'PHP'
        <?php

        namespace App;

        use Arzcode\FilamentHead\FilamentHeadPlugin;

        return fn ($panel) => $panel
            ->id('admin');
        PHP);

    new PanelProviderRegistrar(app())->register($path);

    expect(substr_count(file_get_contents($path), 'use Arzcode\FilamentHead\FilamentHeadPlugin;'))->toBe(1)
        ->and(file_get_contents($path))->toContain("    ->plugin(FilamentHeadPlugin::make())\n    ->id('admin');");

    unlink($path);
});

it('leaves the file alone when the id call does not start a line', function (): void {
    $original = <<<'PHP'
        <?php

        namespace App;

        use Filament\Panel;

        return fn (Panel $panel) => $panel->id('admin')->path('admin');
        PHP;

    $path = panelProviderFile($original);

    expect(new PanelProviderRegistrar(app())->register($path))->toBeFalse()
        ->and(file_get_contents($path))->toBe($original);

    unlink($path);
});

it('finds the loaded panel providers and their ids', function (): void {
    $panels = new PanelProviderRegistrar(app())->discover();

    expect($panels)->toContain('admin')
        ->and(array_keys($panels))->each->not->toContain(DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR);
});
