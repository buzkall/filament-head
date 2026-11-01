<?php

use Arzcode\FilamentHead\Models\HeadMetadata;
use Arzcode\FilamentHead\Schemas\HeadMetadataFields;
use Arzcode\FilamentHead\Tests\Fixtures\Models\Post;
use Arzcode\FilamentHead\Tests\Fixtures\Models\User;
use Arzcode\FilamentHead\Tests\TestCase;
use Filament\Schemas\Components\Section;
use Illuminate\Support\Str;
use Livewire\Features\SupportTesting\Testable;

uses(TestCase::class)->in(__DIR__);

/**
 * @param  array<string, mixed>  $attributes
 */
function makeUser(array $attributes = []): User
{
    return User::query()->create([
        'name' => 'Test User',
        'email' => Str::random(12).'@example.com',
        'password' => 'password',
        ...$attributes,
    ]);
}

/**
 * @param  array<string, mixed>  $attributes
 */
function makePost(array $attributes = []): Post
{
    return Post::query()->create([
        'title' => 'Post title',
        'body' => 'Post body.',
        ...$attributes,
    ]);
}

/**
 * @param  array<string, mixed>  $attributes
 */
function storeMetadata(Post $post, array $attributes): HeadMetadata
{
    return $post->headMetadata()->create($attributes);
}

/**
 * The rendered <head> of the fixture layout, after applyHead().
 */
function renderHead(Post $post): string
{
    return test()->get('/posts/'.$post->getKey())->getContent();
}

/**
 * @return array<int, string>
 */
function formComponentClasses(object $livewire): array
{
    $classes = [];

    $walk = function ($schema) use (&$walk, &$classes): void {
        foreach ($schema->getComponents(withHidden: true) as $component) {
            $classes[] = $component::class;

            foreach ($component->getChildSchemas() as $child) {
                $walk($child);
            }
        }
    };

    $walk($livewire->instance()->getSchema('form'));

    return $classes;
}

/**
 * Load the lazily loaded section's content, as opening it in the browser does.
 *
 * Filament answers that call with a partial render, which the test's HTML does not
 * include; the refresh renders the whole page with the section now loaded. On a
 * Filament without lazily loaded schemas the fields render with the page already.
 */
function openHeadSection(Testable $livewire): Testable
{
    if (! HeadMetadataFields::supportsLazyLoading()) {
        return $livewire;
    }

    return $livewire
        ->call('loadDeferredSchema', 'form.'.HeadMetadataFields::RELATIONSHIP)
        ->call('$refresh');
}

/**
 * The section HeadMetadataFields wraps its fields in, off the mounted form.
 */
function headMetadataSection(object $livewire): Section
{
    $find = function ($schema) use (&$find): ?Section {
        foreach ($schema->getComponents(withHidden: true) as $component) {
            if ($component instanceof Section) {
                return $component;
            }

            foreach ($component->getChildSchemas() as $child) {
                if ($section = $find($child)) {
                    return $section;
                }
            }
        }

        return null;
    };

    return $find($livewire->instance()->getSchema('form'));
}
