<?php

use Arzcode\FilamentHead\FilamentHeadPlugin;
use Arzcode\FilamentHead\Schemas\HeadMetadataFields;

it('returns null for everything it was not given', function (): void {
    $plugin = FilamentHeadPlugin::make();

    expect($plugin->getId())->toBe('filament-head')
        ->and($plugin->getLocales())->toBeNull()
        ->and($plugin->getDisk())->toBeNull()
        ->and($plugin->getDirectory())->toBeNull()
        ->and($plugin->getTitleLimit())->toBeNull()
        ->and($plugin->getDescriptionLimit())->toBeNull();
});

it('evaluates closures passed to its setters', function (): void {
    $plugin = FilamentHeadPlugin::make()
        ->locales(fn (): array => ['es', 'en'])
        ->disk(fn (): string => 'media')
        ->directory(fn (): string => 'seo')
        ->titleLimit(fn (): int => 55)
        ->descriptionLimit(fn (): int => 150);

    expect($plugin->getLocales())->toBe(['es', 'en'])
        ->and($plugin->getDisk())->toBe('media')
        ->and($plugin->getDirectory())->toBe('seo')
        ->and($plugin->getTitleLimit())->toBe(55)
        ->and($plugin->getDescriptionLimit())->toBe(150);
});

it('is not required: the component falls back to config', function (): void {
    config()->set('filament-head.locales', ['es', 'ca']);

    expect(FilamentHeadPlugin::get())->toBeNull()
        ->and(HeadMetadataFields::make()->getLocales())->toBe(['es', 'ca']);
});

it('uses the application locale when nothing configures one', function (): void {
    config()->set('filament-head.locales', null);
    app()->setLocale('eu');

    expect(HeadMetadataFields::make()->getLocales())->toBe(['eu']);
});
