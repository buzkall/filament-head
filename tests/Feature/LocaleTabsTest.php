<?php

use Arzcode\FilamentHead\FilamentHeadPlugin;
use Arzcode\FilamentHead\Schemas\HeadMetadataFields;
use Arzcode\FilamentHead\Tests\Fixtures\Resources\Pages\EditPost;
use Filament\Schemas\Components\Tabs;

use function Pest\Livewire\livewire;

it('renders no tabs for a single locale', function (): void {
    $this->actingAs(makeUser());
    $post = makePost();

    $component = livewire(EditPost::class, ['record' => $post->getKey()])
        ->assertFormFieldExists('headMetadata.title.'.app()->getLocale());

    expect(formComponentClasses($component))->not->toContain(Tabs::class);
});

it('renders a tab per locale and saves every one', function (): void {
    $this->rebootWith(['filament-head.locales' => ['es', 'en']]);
    $this->actingAs(makeUser());

    $post = makePost();

    $component = livewire(EditPost::class, ['record' => $post->getKey()]);

    expect(formComponentClasses($component))->toContain(Tabs::class);

    $component
        ->fillForm([
            'headMetadata.title.es' => 'Título',
            'headMetadata.title.en' => 'Title',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($post->refresh()->headMetadata->title)->toBe(['es' => 'Título', 'en' => 'Title']);
});

it('takes the locales from the plugin when one is registered', function (): void {
    $this->rebootWith(
        configurePlugin: fn (FilamentHeadPlugin $plugin): FilamentHeadPlugin => $plugin->locales(['fr', 'de']),
    );
    $this->actingAs(makeUser());

    $post = makePost();

    livewire(EditPost::class, ['record' => $post->getKey()])
        ->assertFormFieldExists('headMetadata.title.fr')
        ->assertFormFieldExists('headMetadata.title.de');
});

it('lets ->locales() on the component win over the plugin', function (): void {
    $this->rebootWith(
        ['filament-head.locales' => ['es']],
        configurePlugin: fn (FilamentHeadPlugin $plugin): FilamentHeadPlugin => $plugin->locales(['fr']),
        configureFields: fn (HeadMetadataFields $fields): HeadMetadataFields => $fields->locales(['ca', 'en']),
    );
    $this->actingAs(makeUser());

    $post = makePost();

    livewire(EditPost::class, ['record' => $post->getKey()])
        ->assertFormFieldExists('headMetadata.title.ca')
        ->assertFormFieldExists('headMetadata.title.en')
        ->assertFormFieldDoesNotExist('headMetadata.title.fr');
});

it('applies the value for the active locale', function (): void {
    $post = makePost();
    storeMetadata($post, ['title' => ['es' => 'Título', 'en' => 'Title']]);

    app()->setLocale('en');
    expect(renderHead($post))->toContain('<title>Title</title>');

    app()->setLocale('es');
    expect(renderHead($post->fresh()))->toContain('<title>Título</title>');
});

it('applies the fallback locale when the active one is missing', function (): void {
    config()->set('app.fallback_locale', 'es');
    app()->setLocale('en');

    $post = makePost();
    storeMetadata($post, ['title' => ['es' => 'Título', 'en' => '']]);

    expect(renderHead($post))->toContain('<title>Título</title>');
});

it('applies the canonical url for the active locale', function (): void {
    $post = makePost();
    storeMetadata($post, ['canonical_url' => [
        'es' => 'https://example.com/es/post',
        'en' => 'https://example.com/en/post',
    ]]);

    app()->setLocale('en');
    expect(renderHead($post))->toContain('<link rel="canonical" href="https://example.com/en/post">');

    app()->setLocale('es');
    expect(renderHead($post->fresh()))->toContain('<link rel="canonical" href="https://example.com/es/post">');
});

it('never borrows another locale canonical url', function (): void {
    config()->set('app.fallback_locale', 'es');
    app()->setLocale('en');

    $post = makePost();
    storeMetadata($post, [
        'title' => ['es' => 'Título'],
        'canonical_url' => ['es' => 'https://example.com/es/post'],
    ]);

    $html = renderHead($post);

    // the title still falls back, but pointing the English page at the Spanish url
    // would ask search engines to drop it, so a blank canonical stays blank
    expect($html)->toContain('<title>Título</title>')
        ->and($html)->not->toContain('rel="canonical"');
});
