<?php

use Arzcode\FilamentHead\FilamentHeadPlugin;
use Arzcode\FilamentHead\Schemas\HeadMetadataFields;
use Arzcode\FilamentHead\Tests\Fixtures\Models\Post;
use Filament\Facades\Filament;
use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Database\Eloquent\Model;

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

it('returns null from for() when the panel has no plugin', function (): void {
    expect(FilamentHeadPlugin::for(Filament::getPanel('admin')))->toBeNull();
});

it('returns the plugin from for() when the panel registers one', function (): void {
    $this->rebootWith(
        configurePlugin: fn (FilamentHeadPlugin $plugin): FilamentHeadPlugin => $plugin->titleLimit(42),
    );

    expect(FilamentHeadPlugin::for(Filament::getPanel('admin'))?->getTitleLimit())->toBe(42);
});

it('hands the default og image closure the record however it asks for it', function (Closure $callback): void {
    $post = makePost();

    $url = FilamentHeadPlugin::make()->defaultOgImage($callback)->getDefaultOgImage($post);

    expect($url)->toBe("https://example.com/cards/{$post->getKey()}.jpg");
})->with([
    'named $record' => fn ($record): string => "https://example.com/cards/{$record->getKey()}.jpg",
    'named $model' => fn ($model): string => "https://example.com/cards/{$model->getKey()}.jpg",
    'typed as its class' => fn (Post $post): string => "https://example.com/cards/{$post->getKey()}.jpg",
    'typed as Model' => fn (Model $m): string => "https://example.com/cards/{$m->getKey()}.jpg",
    'typed as an interface' => fn (UrlRoutable $r): string => "https://example.com/cards/{$r->getRouteKey()}.jpg",
]);

it('returns no default og image when none is configured', function (): void {
    expect(FilamentHeadPlugin::make()->getDefaultOgImage(makePost()))->toBeNull();
});
