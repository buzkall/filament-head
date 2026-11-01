<?php

namespace Arzcode\FilamentHead;

use Arzcode\FilamentHead\Support\PanelProviderRegistrar;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentHeadServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('filament-head')
            ->hasConfigFile()
            ->hasMigration('create_head_metadata_table')
            ->hasTranslations()
            ->hasInstallCommand(function (InstallCommand $command): void {
                $command
                    ->publishConfigFile()
                    ->publishMigrations()
                    ->askToRunMigrations()
                    ->endWith(fn (InstallCommand $command) => $this->askToRegisterPlugin($command));
            });
    }

    protected function askToRegisterPlugin(InstallCommand $command): void
    {
        $registrar = new PanelProviderRegistrar($this->app);

        foreach ($registrar->discover() as $path => $panel) {
            $file = str_replace(base_path().DIRECTORY_SEPARATOR, '', $path);

            if ($registrar->isRegistered($path)) {
                $command->comment("FilamentHeadPlugin is already registered in the {$panel} panel.");

                continue;
            }

            if (! $command->confirm("Register FilamentHeadPlugin in the {$panel} panel ({$file})?", true)) {
                continue;
            }

            if ($registrar->register($path)) {
                $command->info("Registered FilamentHeadPlugin in the {$panel} panel.");

                continue;
            }

            $command->warn("Could not place the plugin in {$file}. Add it to the panel by hand:");
            $command->line('    '.PanelProviderRegistrar::snippet());
        }
    }
}
