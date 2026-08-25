<?php

use Arzcode\FilamentHead\Models\HeadMetadata;
use Arzcode\FilamentHead\Schemas\HeadMetadataFields;
use Arzcode\FilamentHead\Tests\Fixtures\Models\Post;
use Arzcode\FilamentHead\Tests\Fixtures\Resources\Pages\CreatePost;
use Arzcode\FilamentHead\Tests\Fixtures\Resources\Pages\EditPost;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->actingAs(makeUser());
});

it('exposes the metadata fields under the relationship state path', function (): void {
    $post = makePost();

    livewire(EditPost::class, ['record' => $post->getKey()])
        ->assertFormFieldExists('headMetadata.title.en')
        ->assertFormFieldExists('headMetadata.description.en')
        ->assertFormFieldExists('headMetadata.og_title.en')
        ->assertFormFieldExists('headMetadata.og_description.en')
        ->assertFormFieldExists('headMetadata.og_image')
        ->assertFormFieldExists('headMetadata.og_type')
        ->assertFormFieldExists('headMetadata.twitter_card')
        ->assertFormFieldExists('headMetadata.canonical_url.en')
        ->assertFormFieldExists('headMetadata.robots');
});

it('saves the metadata from the edit page', function (): void {
    $post = makePost();

    livewire(EditPost::class, ['record' => $post->getKey()])
        ->fillForm([
            'headMetadata.title.en' => 'Custom',
            'headMetadata.description.en' => 'Desc',
            'headMetadata.robots' => 'noindex, follow',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $metadata = $post->refresh()->headMetadata;

    expect($metadata)->toBeInstanceOf(HeadMetadata::class)
        ->and($metadata->model_type)->toBe(Post::class)
        ->and($metadata->model_id)->toBe($post->getKey())
        ->and($metadata->title['en'])->toBe('Custom')
        ->and($metadata->description['en'])->toBe('Desc')
        ->and($metadata->robots)->toBe('noindex, follow');
});

it('updates the metadata that already exists', function (): void {
    $post = makePost();
    storeMetadata($post, ['title' => ['en' => 'Old']]);

    livewire(EditPost::class, ['record' => $post->getKey()])
        ->assertFormSet(['headMetadata.title.en' => 'Old'])
        ->fillForm(['headMetadata.title.en' => 'New'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(HeadMetadata::query()->count())->toBe(1)
        ->and($post->refresh()->headMetadata->title['en'])->toBe('New');
});

it('creates the record and its metadata together', function (): void {
    livewire(CreatePost::class)
        ->fillForm([
            'title' => 'A new post',
            'headMetadata.title.en' => 'Created',
            'headMetadata.canonical_url.en' => 'https://example.com/a-new-post',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $post = Post::query()->firstOrFail();

    expect($post->title)->toBe('A new post')
        ->and($post->headMetadata->title['en'])->toBe('Created')
        ->and($post->headMetadata->canonical_url['en'])->toBe('https://example.com/a-new-post');
});

it('hides the fields passed to without()', function (): void {
    $this->rebootWith(configureFields: fn (HeadMetadataFields $fields): HeadMetadataFields => $fields->without(['robots', 'twitter_card']));
    $this->actingAs(makeUser());

    $post = makePost();

    livewire(EditPost::class, ['record' => $post->getKey()])
        ->assertFormFieldExists('headMetadata.title.en')
        ->assertFormFieldDoesNotExist('headMetadata.robots')
        ->assertFormFieldDoesNotExist('headMetadata.twitter_card')
        ->assertFormFieldExists('headMetadata.og_type');
});

it('validates the canonical url as a url', function (): void {
    $post = makePost();

    livewire(EditPost::class, ['record' => $post->getKey()])
        ->fillForm(['headMetadata.canonical_url.en' => 'not a url'])
        ->call('save')
        ->assertHasFormErrors(['headMetadata.canonical_url.en']);
});

it('renders and applies cleanly when the section is left entirely empty', function (): void {
    livewire(CreatePost::class)
        ->fillForm(['title' => 'No metadata'])
        ->call('create')
        ->assertHasNoFormErrors();

    $post = Post::query()->firstOrFail();

    // Filament may create a row of nulls for the relationship; either way nothing
    // is stored, so applyHead() falls through to the model defaults.
    expect($post->headMetadata?->translated('title'))->toBeNull()
        ->and(renderHead($post))->toContain('<title>No metadata</title>');
});

it('refuses to hide a field that is not optional', function (string $field): void {
    expect(fn () => HeadMetadataFields::make()->without([$field]))
        ->toThrow(LogicException::class, $field);
})->with(['title', 'description', 'og_titel']);

it('accepts every documented optional field', function (): void {
    $fields = HeadMetadataFields::make()->without(HeadMetadataFields::OPTIONAL_FIELDS);

    expect($fields)->toBeInstanceOf(HeadMetadataFields::class);
});
