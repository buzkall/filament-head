<?php

namespace Arzcode\FilamentHead\Support;

use Arzcode\FilamentHead\FilamentHeadPlugin;
use Filament\PanelProvider;
use Illuminate\Contracts\Foundation\Application;
use ReflectionClass;

/**
 * Finds the application's panel providers and adds FilamentHeadPlugin to them,
 * for the install command.
 */
class PanelProviderRegistrar
{
    public function __construct(protected Application $app) {}

    /**
     * The panel providers the application has loaded, outside vendor, keyed by
     * file path, with the panel ID each one sets.
     *
     * @return array<string, string>
     */
    public function discover(): array
    {
        $panels = [];

        foreach ($this->app->getProviders(PanelProvider::class) as $provider) {
            $path = new ReflectionClass($provider)->getFileName();

            if ($path === false || str_contains($path, DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR)) {
                continue;
            }

            $panels[$path] = $this->panelId((string) file_get_contents($path)) ?? class_basename($provider);
        }

        return $panels;
    }

    public function isRegistered(string $path): bool
    {
        return str_contains((string) file_get_contents($path), 'FilamentHeadPlugin');
    }

    /**
     * Adds `->plugin(FilamentHeadPlugin::make())` just before the panel's
     * `->id()` call. Returns false, leaving the file untouched, when that call
     * does not start a line of its own.
     */
    public function register(string $path): bool
    {
        $contents = (string) file_get_contents($path);

        if (! preg_match('/^([ \t]*)->id\(/m', $contents, $match, PREG_OFFSET_CAPTURE)) {
            return false;
        }

        [$indent, $offset] = $match[1];

        [$contents, $imported] = $this->import($contents);

        if ($imported) {
            preg_match('/^[ \t]*->id\(/m', $contents, $match, PREG_OFFSET_CAPTURE);
            $offset = $match[0][1];
        }

        $class = $imported ? class_basename(FilamentHeadPlugin::class) : '\\'.FilamentHeadPlugin::class;
        $line = $indent."->plugin({$class}::make())\n";

        file_put_contents($path, substr_replace($contents, $line, $offset, 0));

        return true;
    }

    /**
     * The code to add by hand when register() cannot place it.
     */
    public static function snippet(): string
    {
        return '->plugin(\\'.FilamentHeadPlugin::class.'::make())';
    }

    protected function panelId(string $contents): ?string
    {
        return preg_match('/->id\(\s*([\'"])(.+?)\1\s*\)/', $contents, $match) ? $match[2] : null;
    }

    /**
     * Adds the `use` statement after the file's last top-level import, or after
     * its namespace. Reports false when it found neither.
     *
     * @return array{0: string, 1: bool}
     */
    protected function import(string $contents): array
    {
        $statement = 'use '.FilamentHeadPlugin::class.';';

        if (str_contains($contents, $statement)) {
            return [$contents, true];
        }

        if (preg_match_all('/^use [^;]+;[ \t]*$/m', $contents, $matches, PREG_OFFSET_CAPTURE)) {
            [$last, $offset] = end($matches[0]);

            return [substr_replace($contents, "\n".$statement, $offset + strlen($last), 0), true];
        }

        if (preg_match('/^namespace [^;]+;[ \t]*$/m', $contents, $match, PREG_OFFSET_CAPTURE)) {
            [$namespace, $offset] = $match[0];

            return [substr_replace($contents, "\n\n".$statement, $offset + strlen($namespace), 0), true];
        }

        return [$contents, false];
    }
}
