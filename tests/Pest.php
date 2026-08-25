<?php

use Arzcode\FilamentHead\Models\HeadMetadata;
use Arzcode\FilamentHead\Tests\Fixtures\Models\Post;
use Arzcode\FilamentHead\Tests\Fixtures\Models\User;
use Arzcode\FilamentHead\Tests\TestCase;
use Illuminate\Support\Str;

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
