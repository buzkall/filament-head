<?php

use Arzcode\FilamentHead\Tests\Fixtures\Resources\Pages\EditPost;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->actingAs(makeUser());
});

it('counts both fields against their own limit', function (): void {
    $post = makePost();

    livewire(EditPost::class, ['record' => $post->getKey()])
        ->assertSee('0/60 characters')
        ->assertSee('0/160 characters')
        ->assertDontSeeHtml('fi-color-danger');
});

it('recounts as the value changes', function (): void {
    $post = makePost();

    livewire(EditPost::class, ['record' => $post->getKey()])
        ->fillForm(['headMetadata.title.en' => 'Twelve chars'])
        ->assertSee('12/60 characters')
        ->assertDontSeeHtml('fi-color-danger');
});

it('turns the counter red past the limit', function (): void {
    $post = makePost();

    livewire(EditPost::class, ['record' => $post->getKey()])
        ->fillForm(['headMetadata.title.en' => str_repeat('a', 65)])
        ->assertSee('65/60 characters — longer than recommended')
        ->assertSeeHtml('fi-color-danger');
});
