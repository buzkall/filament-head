<?php

use Arzcode\FilamentHead\Models\HeadMetadata;

it('returns the value for the active locale', function (): void {
    app()->setLocale('en');

    $metadata = new HeadMetadata(['title' => ['en' => 'English', 'es' => 'Español']]);

    expect($metadata->translated('title'))->toBe('English')
        ->and($metadata->translated('title', 'es'))->toBe('Español');
});

it('falls back to the configured fallback locale', function (): void {
    app()->setLocale('ca');
    config()->set('filament-head.fallback_locale', 'es');

    $metadata = new HeadMetadata(['title' => ['en' => 'English', 'es' => 'Español']]);

    expect($metadata->translated('title'))->toBe('Español');
});

it('falls back to the application fallback locale when none is configured', function (): void {
    app()->setLocale('ca');
    config()->set('filament-head.fallback_locale', null);
    config()->set('app.fallback_locale', 'en');

    $metadata = new HeadMetadata(['title' => ['en' => 'English', 'es' => 'Español']]);

    expect($metadata->translated('title'))->toBe('English');
});

it('falls back to the first non-empty translation', function (): void {
    app()->setLocale('ca');
    config()->set('filament-head.fallback_locale', 'de');
    config()->set('app.fallback_locale', 'de');

    $metadata = new HeadMetadata(['title' => ['en' => '', 'es' => 'Español']]);

    expect($metadata->translated('title'))->toBe('Español');
});

it('skips empty strings for the active locale', function (): void {
    app()->setLocale('en');
    config()->set('filament-head.fallback_locale', 'es');

    $metadata = new HeadMetadata(['title' => ['en' => '', 'es' => 'Español']]);

    expect($metadata->translated('title'))->toBe('Español');
});

it('returns null when nothing is translated', function (): void {
    $metadata = new HeadMetadata(['title' => ['en' => '', 'es' => null]]);

    expect($metadata->translated('title'))->toBeNull()
        ->and((new HeadMetadata)->translated('title'))->toBeNull();
});

it('builds an absolute url for the stored og image', function (): void {
    $metadata = new HeadMetadata(['og_image' => 'head-metadata/cover.png']);

    expect($metadata->ogImageUrl())->toBe('http://localhost/storage/head-metadata/cover.png');
});

it('returns no og image url when none is stored', function (): void {
    expect((new HeadMetadata)->ogImageUrl())->toBeNull()
        ->and((new HeadMetadata(['og_image' => '']))->ogImageUrl())->toBeNull();
});
