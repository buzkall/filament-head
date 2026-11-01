<?php

use Arzcode\FilamentHead\FilamentHeadPlugin;
use Arzcode\FilamentHead\Schemas\HeadMetadataFields;
use Arzcode\FilamentHead\Tests\Fixtures\Models\Post;
use Arzcode\FilamentHead\Tests\Fixtures\Resources\Pages\CreatePost;
use Arzcode\FilamentHead\Tests\Fixtures\Resources\Pages\EditPost;
use Laravel\Head\Enums\OgType;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->actingAs(makeUser());
});

it('shows the record defaults as the placeholders', function (): void {
    $post = makePost(['title' => 'Default title', 'body' => 'Default body.']);

    openHeadSection(livewire(EditPost::class, ['record' => $post->getKey()]))
        ->assertSeeHtml('placeholder="Default title"')
        ->assertSeeHtml('placeholder="Default body."');
});

it('shows the title and description the Open Graph fields fall back to', function (): void {
    $post = makePost(['title' => 'Default title', 'body' => 'Default body.']);

    openHeadSection(livewire(EditPost::class, ['record' => $post->getKey()]))
        ->fillForm([
            'headMetadata.title.en' => 'Typed title',
            'headMetadata.description.en' => 'Typed description',
        ])
        ->assertSeeHtml('placeholder="Typed title"')
        ->assertSeeHtml('placeholder="Typed description"');
});

it('shows a blank robots field as indexed and followed', function (): void {
    $post = makePost();

    openHeadSection(livewire(EditPost::class, ['record' => $post->getKey()]))
        ->assertSee('Index and follow links (default)');
});

it('translates the select options', function (): void {
    app()->setLocale('es');
    $post = makePost();

    openHeadSection(livewire(EditPost::class, ['record' => $post->getKey()]))
        ->assertSee('Resumen con imagen grande')
        ->assertSee('Serie de televisión')
        ->assertDontSee('Summary With Large Image');
});

it('previews the sharing image the plugin supplies', function (): void {
    $this->rebootWith(configurePlugin: fn (FilamentHeadPlugin $plugin): FilamentHeadPlugin => $plugin
        ->defaultOgImage(fn (Post $record): string => "https://example.com/cards/{$record->getKey()}.jpg"));
    $this->actingAs(makeUser());

    $post = makePost();

    openHeadSection(livewire(EditPost::class, ['record' => $post->getKey()]))
        ->assertSeeHtml("src=\"https://example.com/cards/{$post->getKey()}.jpg\"");
});

it('shows no preview when the plugin supplies no image', function (): void {
    $this->rebootWith(configurePlugin: fn (FilamentHeadPlugin $plugin): FilamentHeadPlugin => $plugin
        ->defaultOgImage(fn (): ?string => null));
    $this->actingAs(makeUser());

    $post = makePost();

    openHeadSection(livewire(EditPost::class, ['record' => $post->getKey()]))
        ->assertDontSee('The image shared today');
});

it('keeps the empty placeholders on create, where there is no record yet', function (): void {
    openHeadSection(livewire(CreatePost::class))
        ->assertDontSeeHtml('placeholder="Post title"')
        ->assertSee('Select an option');
});

it('previews no image whose url is not http, https or root relative', function (string $url): void {
    $this->rebootWith(configurePlugin: fn (FilamentHeadPlugin $plugin): FilamentHeadPlugin => $plugin
        ->defaultOgImage(fn (): string => $url));
    $this->actingAs(makeUser());

    openHeadSection(livewire(EditPost::class, ['record' => makePost()->getKey()]))
        ->assertDontSee(__('filament-head::filament-head.helpers.og_image_current'))
        ->assertDontSeeHtml('<img src=');
})->with([
    'javascript' => 'javascript:alert(1)',
    'javascript, padded and upper case' => '  JavaScript:alert(1)',
    'data' => 'data:image/svg+xml;base64,PHN2Zz48L3N2Zz4=',
    'relative path' => 'cards/1.jpg',
]);

it('previews a root relative image', function (): void {
    $this->rebootWith(configurePlugin: fn (FilamentHeadPlugin $plugin): FilamentHeadPlugin => $plugin
        ->defaultOgImage(fn (): string => '/cards/1.jpg'));
    $this->actingAs(makeUser());

    openHeadSection(livewire(EditPost::class, ['record' => makePost()->getKey()]))
        ->assertSeeHtml('src="/cards/1.jpg"');
});

it('explains that a robots option overrides the default while a blank field keeps it', function (): void {
    openHeadSection(livewire(EditPost::class, ['record' => makePost()->getKey()]))
        ->assertSee(__('filament-head::filament-head.helpers.robots'));
});

it('labels an option this package has not translated by its case name', function (): void {
    $options = (fn (): array => $this->enumOptions(OgType::cases(), 'og_types_not_translated'))
        ->call(HeadMetadataFields::make());

    expect($options)
        ->toHaveKey('video.tv_show', 'Video Tv Show')
        ->toHaveKey('music.radio_station', 'Music Radio Station')
        ->each->not->toContain('filament-head::');
});
