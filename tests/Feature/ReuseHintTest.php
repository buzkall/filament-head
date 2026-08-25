<?php

use Arzcode\FilamentHead\Tests\Fixtures\Resources\Pages\EditPost;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->actingAs(makeUser());
});

it('names the title the og title would reuse', function (): void {
    $post = makePost();

    livewire(EditPost::class, ['record' => $post->getKey()])
        ->fillForm(['headMetadata.title.en' => 'The reused title'])
        ->assertSee('Leave blank to reuse the title: “The reused title”')
        ->assertSee('Leave blank to reuse the meta description.');
});

it('names the meta description the og description would reuse', function (): void {
    $post = makePost();

    livewire(EditPost::class, ['record' => $post->getKey()])
        ->fillForm(['headMetadata.description.en' => 'The reused description'])
        ->assertSee('Leave blank to reuse the meta description: “The reused description”');
});

it('falls back to the plain sentence while the sibling field is empty', function (): void {
    $post = makePost();

    livewire(EditPost::class, ['record' => $post->getKey()])
        ->assertSee('Leave blank to reuse the title.')
        ->assertSee('Leave blank to reuse the meta description.');
});

it('shortens a long value and squishes its whitespace', function (): void {
    $post = makePost();

    livewire(EditPost::class, ['record' => $post->getKey()])
        ->fillForm(['headMetadata.description.en' => "A   description\nlong enough to be cut off before it fills the whole helper line."])
        ->assertSee('A description long enough to be cut off before it fills the...');
});

it('quotes each locale its own sibling value', function (): void {
    $this->rebootWith(['filament-head.locales' => ['en', 'es']]);
    $this->actingAs(makeUser());

    $post = makePost();

    livewire(EditPost::class, ['record' => $post->getKey()])
        ->fillForm([
            'headMetadata.title.en' => 'English title',
            'headMetadata.title.es' => 'Título español',
        ])
        ->assertSee('Leave blank to reuse the title: “English title”')
        ->assertSee('Leave blank to reuse the title: “Título español”');
});
