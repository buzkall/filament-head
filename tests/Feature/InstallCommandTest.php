<?php

use Illuminate\Support\Str;

/**
 * Writes a panel provider to a temporary file, loads it and registers it, so
 * the install command finds a panel without the plugin.
 */
function temporaryPanelProvider(string $id): string
{
    $class = 'TempPanelProvider'.Str::random(8);
    $path = realpath(sys_get_temp_dir()).DIRECTORY_SEPARATOR.$class.'.php';

    file_put_contents($path, str_replace(
        ['{{ namespace }}', '{{ class }}', '{{ id }}'],
        ['Arzcode\FilamentHead\Tests\Temp', $class, $id],
        file_get_contents(__DIR__.'/../Fixtures/stubs/PanelProvider.php.stub'),
    ));

    require $path;

    app()->register('Arzcode\FilamentHead\Tests\Temp\\'.$class);

    return $path;
}

it('publishes the config file and the migration', function (): void {
    $this->artisan('filament-head:install')
        ->expectsQuestion('Would you like to run the migrations now?', false)
        ->expectsOutputToContain('FilamentHeadPlugin is already registered in the admin panel.')
        ->assertSuccessful();

    expect(config_path('filament-head.php'))->toBeFile()
        ->and(glob(database_path('migrations/*_create_head_metadata_table.php')))->not->toBeEmpty();
});

it('offers to register the plugin in each panel that lacks it', function (): void {
    $accepted = temporaryPanelProvider('app');
    $declined = temporaryPanelProvider('backoffice');
    $this->temporaryFiles = [$accepted, $declined];

    $this->artisan('filament-head:install')
        ->expectsQuestion('Would you like to run the migrations now?', false)
        ->expectsConfirmation("Register FilamentHeadPlugin in the app panel ({$accepted})?", 'yes')
        ->expectsConfirmation("Register FilamentHeadPlugin in the backoffice panel ({$declined})?", 'no')
        ->expectsOutputToContain('Registered FilamentHeadPlugin in the app panel.')
        ->assertSuccessful();

    expect(file_get_contents($accepted))
        ->toContain('use Arzcode\FilamentHead\FilamentHeadPlugin;')
        ->toContain("->plugin(FilamentHeadPlugin::make())\n            ->id('app')")
        ->and(file_get_contents($declined))->not->toContain('FilamentHeadPlugin');
});

afterEach(function (): void {
    @unlink(config_path('filament-head.php'));

    foreach (glob(database_path('migrations/*_create_head_metadata_table.php')) as $migration) {
        @unlink($migration);
    }

    foreach ($this->temporaryFiles ?? [] as $file) {
        @unlink($file);
    }
});
