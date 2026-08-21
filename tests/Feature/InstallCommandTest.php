<?php

it('publishes the config file and the migration', function (): void {
    $this->artisan('filament-head:install')
        ->expectsQuestion('Would you like to run the migrations now?', false)
        ->expectsQuestion('Would you like to star our repo on GitHub?', false)
        ->assertSuccessful();

    expect(config_path('filament-head.php'))->toBeFile()
        ->and(glob(database_path('migrations/*_create_head_metadata_table.php')))->not->toBeEmpty();
});

afterEach(function (): void {
    @unlink(config_path('filament-head.php'));

    foreach (glob(database_path('migrations/*_create_head_metadata_table.php')) as $migration) {
        @unlink($migration);
    }
});
