<?php

use Illuminate\Support\Arr;

$locales = ['en', 'es', 'ca'];

function localePath(string $locale): string
{
    return __DIR__."/../../resources/lang/{$locale}/filament-head.php";
}

/**
 * @return array<int, string>
 */
function localeKeys(string $locale): array
{
    $path = localePath($locale);

    expect($path)->toBeFile();

    return array_keys(Arr::dot(require $path));
}

it('ships identical translation keys in every locale', function () use ($locales): void {
    $reference = localeKeys('en');

    sort($reference);

    foreach ($locales as $locale) {
        $keys = localeKeys($locale);
        sort($keys);

        expect($keys)->toBe($reference, "Locale [{$locale}] has a different key set to [en].");
    }
});

it('translates every key to a non-empty string', function (string $locale): void {
    foreach (Arr::dot(require localePath($locale)) as $key => $value) {
        expect($value)->toBeString()->not->toBe('', "Key [{$key}] is empty in [{$locale}].");
    }
})->with($locales);

it('resolves the shipped keys through the translator', function (string $locale): void {
    app()->setLocale($locale);

    expect(__('filament-head::filament-head.section.heading'))
        ->not->toContain('filament-head::')
        ->and(__('filament-head::filament-head.helpers.counter', ['count' => 12, 'limit' => 60]))
        ->toContain('12')
        ->toContain('60');
})->with($locales);

// Guard, not a parser: user-facing setters must never receive a bare literal.
it('has no untranslated literal strings in src', function (): void {
    $offenders = [];

    $methods = ['label', 'heading', 'title', 'body', 'helperText', 'placeholder', 'hint', 'tooltip', 'description'];
    $pattern = '/->('.implode('|', $methods).')\(\s*[\'"]/';

    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__.'/../../src')) as $file) {
        if ($file->getExtension() !== 'php') {
            continue;
        }

        foreach (file($file->getPathname()) as $number => $line) {
            if (preg_match($pattern, $line)) {
                $offenders[] = $file->getFilename().':'.($number + 1).' — '.trim($line);
            }
        }
    }

    expect($offenders)->toBe([]);
});
