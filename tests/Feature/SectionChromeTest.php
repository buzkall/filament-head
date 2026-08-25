<?php

use Arzcode\FilamentHead\Schemas\HeadMetadataFields;
use Arzcode\FilamentHead\Tests\Fixtures\Resources\Pages\EditPost;
use Filament\Schemas\Components\Section;

use function Pest\Livewire\livewire;

it('wraps the fields in a collapsible section by default', function (): void {
    $this->actingAs(makeUser());
    $post = makePost();

    $component = livewire(EditPost::class, ['record' => $post->getKey()]);

    expect(formComponentClasses($component))->toContain(Section::class);

    $component->assertSee(__('filament-head::filament-head.section.heading'));
});

it('renders a custom heading on that section', function (): void {
    $this->rebootWith(
        configureFields: fn (HeadMetadataFields $fields): HeadMetadataFields => $fields->heading('Search engines'),
    );
    $this->actingAs(makeUser());

    livewire(EditPost::class, ['record' => makePost()->getKey()])
        ->assertSee('Search engines')
        ->assertDontSee(__('filament-head::filament-head.section.heading'));
});

it('drops the section entirely for withoutSection()', function (): void {
    $this->rebootWith(
        configureFields: fn (HeadMetadataFields $fields): HeadMetadataFields => $fields->withoutSection(),
    );
    $this->actingAs(makeUser());

    $post = makePost();

    $component = livewire(EditPost::class, ['record' => $post->getKey()]);

    expect(formComponentClasses($component))->not->toContain(Section::class);

    $component
        ->assertDontSee(__('filament-head::filament-head.section.heading'))
        ->assertDontSee(__('filament-head::filament-head.section.description'))
        ->assertFormFieldExists('headMetadata.title.'.app()->getLocale());
});

it('still saves the metadata with no section around the fields', function (): void {
    $this->rebootWith(
        configureFields: fn (HeadMetadataFields $fields): HeadMetadataFields => $fields->withoutSection(),
    );
    $this->actingAs(makeUser());

    $post = makePost();
    $locale = app()->getLocale();

    livewire(EditPost::class, ['record' => $post->getKey()])
        ->fillForm([
            "headMetadata.title.{$locale}" => 'Sectionless',
            'headMetadata.robots' => 'noindex, follow',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($post->refresh()->headMetadata->translated('title'))->toBe('Sectionless')
        ->and($post->headMetadata->robots)->toBe('noindex, follow');
});
