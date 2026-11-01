<?php

use Arzcode\FilamentHead\FilamentHeadPlugin;
use Arzcode\FilamentHead\Schemas\HeadMetadataFields;
use Arzcode\FilamentHead\Tests\Fixtures\Models\Post;
use Arzcode\FilamentHead\Tests\Fixtures\Resources\Pages\EditPost;
use Arzcode\FilamentHead\Tests\Fixtures\Schemas\LegacyHeadMetadataFields;
use Illuminate\Support\Facades\Storage;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->actingAs(makeUser());
});

$withoutLazySchemas = fn (): bool => ! HeadMetadataFields::supportsLazyLoading();

it('renders the fields only once the section is opened', function (): void {
    $post = makePost(['title' => 'Default title']);

    $component = livewire(EditPost::class, ['record' => $post->getKey()])
        ->assertSee(__('filament-head::filament-head.section.heading'))
        ->assertDontSeeHtml('placeholder="Default title"');

    openHeadSection($component)->assertSeeHtml('placeholder="Default title"');
})->skip($withoutLazySchemas, 'Needs Filament 5.8+ lazily loaded schemas.');

it('keeps the fields keyed under the relationship while lazily loaded', function (): void {
    $post = makePost();

    livewire(EditPost::class, ['record' => $post->getKey()])
        ->assertFormFieldExists('headMetadata.title.en')
        ->assertFormFieldExists('headMetadata.robots')
        ->assertFormFieldDoesNotExist('headMetadata.headMetadata.title.en');
});

it('keeps every stored value when saving without opening the section', function (): void {
    // The upload drops a stored path whose file is missing, lazily loaded or not.
    Storage::fake('public')->put('head-metadata/card.jpg', 'image');
    $post = makePost();
    storeMetadata($post, [
        'title' => ['en' => 'Stored title'],
        'description' => ['en' => 'Stored description'],
        'og_title' => ['en' => 'Stored og title'],
        'canonical_url' => ['en' => 'https://example.com/stored'],
        'og_image' => 'head-metadata/card.jpg',
        'og_type' => 'article',
        'twitter_card' => 'summary_large_image',
        'robots' => 'noindex, follow',
    ]);

    livewire(EditPost::class, ['record' => $post->getKey()])
        ->fillForm(['title' => 'Renamed post'])
        ->call('save')
        ->assertHasNoFormErrors();

    $metadata = $post->refresh()->headMetadata;

    expect($post->title)->toBe('Renamed post')
        ->and($metadata->title)->toBe(['en' => 'Stored title'])
        ->and($metadata->description)->toBe(['en' => 'Stored description'])
        ->and($metadata->og_title)->toBe(['en' => 'Stored og title'])
        ->and($metadata->canonical_url)->toBe(['en' => 'https://example.com/stored'])
        ->and($metadata->og_image)->toBe('head-metadata/card.jpg')
        ->and($metadata->og_type)->toBe('article')
        ->and($metadata->twitter_card)->toBe('summary_large_image')
        ->and($metadata->robots)->toBe('noindex, follow');
});

it('loads the section and shows the error when a field inside it fails validation', function (): void {
    $post = makePost();
    storeMetadata($post, ['canonical_url' => ['en' => 'not a url']]);

    $component = livewire(EditPost::class, ['record' => $post->getKey()])
        ->call('save')
        ->assertHasFormErrors(['headMetadata.canonical_url.en']);

    expect($component->instance()->loadedDeferredSchemas)
        ->toHaveKey('form.'.HeadMetadataFields::RELATIONSHIP, true);

    $component->assertSeeHtml('data-validation-error');
})->skip($withoutLazySchemas, 'Needs Filament 5.8+ lazily loaded schemas.');

it('resolves the sharing image only once the section is opened', function (): void {
    $calls = new ArrayObject;

    $this->rebootWith(configurePlugin: fn (FilamentHeadPlugin $plugin): FilamentHeadPlugin => $plugin
        ->defaultOgImage(function () use ($calls): string {
            $calls->append(true);

            return 'https://example.com/card.jpg';
        }));
    $this->actingAs(makeUser());

    $component = livewire(EditPost::class, ['record' => makePost()->getKey()]);

    expect($calls)->toHaveCount(0);

    openHeadSection($component);

    // Two requests — the load and the refresh — at most one call each.
    expect(count($calls))->toBeGreaterThan(0)->toBeLessThanOrEqual(2);
})->skip($withoutLazySchemas, 'Needs Filament 5.8+ lazily loaded schemas.');

it('renders the fields with the page for lazyLoaded(false)', function (): void {
    $this->rebootWith(
        configureFields: fn (HeadMetadataFields $fields): HeadMetadataFields => $fields->lazyLoaded(false),
    );
    $this->actingAs(makeUser());

    livewire(EditPost::class, ['record' => makePost(['title' => 'Default title'])->getKey()])
        ->assertSeeHtml('placeholder="Default title"');

    expect(headMetadataSection(livewire(EditPost::class, ['record' => makePost()->getKey()])))
        ->isCollapsed()->toBeTrue();
});

it('is never lazy without a section', function (): void {
    expect(HeadMetadataFields::make()->withoutSection()->isLazyLoaded())->toBeFalse()
        ->and(HeadMetadataFields::make()->isLazyLoaded())->toBe(HeadMetadataFields::supportsLazyLoading());
});

it('falls back to rendering the fields with the page on Filament without lazy schemas', function (): void {
    $this->rebootWith(
        configureFields: fn (): HeadMetadataFields => LegacyHeadMetadataFields::make(),
    );
    $this->actingAs(makeUser());

    $post = makePost(['title' => 'Default title']);

    livewire(EditPost::class, ['record' => $post->getKey()])
        ->assertSee(__('filament-head::filament-head.section.heading'))
        ->assertSeeHtml('placeholder="Default title"')
        ->assertFormFieldExists('headMetadata.title.en')
        ->fillForm(['headMetadata.title.en' => 'Legacy'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($post->refresh()->headMetadata->translated('title'))->toBe('Legacy');
});

it('resolves the shared fields in the site locale, not the admin one', function (): void {
    Post::$extraDefaults = fn (): array => ['ogType' => app()->getLocale() === 'es' ? 'article' : 'book'];

    $this->rebootWith(
        config: ['app.fallback_locale' => 'en'],
        configurePlugin: fn (FilamentHeadPlugin $plugin): FilamentHeadPlugin => $plugin->locales(['es', 'en']),
    );
    $this->actingAs(makeUser());
    app()->setLocale('es');

    openHeadSection(livewire(EditPost::class, ['record' => makePost()->getKey()]))
        ->assertSee('Libro (por defecto)')
        ->assertDontSee('Artículo (por defecto)');
});
