<?php

use Arzcode\FilamentHead\Tests\Fixtures\Models\ArrayDefaultsPost;

it('renders the stored title and description', function (): void {
    $post = makePost();
    storeMetadata($post, [
        'title' => ['en' => 'Stored title'],
        'description' => ['en' => 'Stored description'],
    ]);

    $html = renderHead($post);

    expect($html)->toContain('<title>Stored title</title>')
        ->and($html)->toContain('<meta name="description" content="Stored description">');
});

it('renders the model defaults when no metadata row exists', function (): void {
    $post = makePost(['title' => 'Default title', 'body' => 'Default body.']);

    $html = renderHead($post);

    expect($html)->toContain('<title>Default title</title>')
        ->and($html)->toContain('<meta name="description" content="Default body.">');
});

it('prefers a stored value over the model default', function (): void {
    $post = makePost(['title' => 'Default title']);
    storeMetadata($post, ['title' => ['en' => 'Stored title']]);

    expect(renderHead($post))->toContain('<title>Stored title</title>');
});

it('falls back per field when only some stored values are filled', function (): void {
    $post = makePost(['title' => 'Default title', 'body' => 'Default body.']);
    storeMetadata($post, [
        'title' => ['en' => ''],
        'description' => ['en' => 'Stored description'],
    ]);

    $html = renderHead($post);

    expect($html)->toContain('<title>Default title</title>')
        ->and($html)->toContain('<meta name="description" content="Stored description">');
});

it('renders an external canonical url unchanged', function (): void {
    $post = makePost();
    storeMetadata($post, ['canonical_url' => 'http://external.example/x']);

    expect(renderHead($post))->toContain('<link rel="canonical" href="http://external.example/x">');
});

it('renders the stored robots directives', function (): void {
    $post = makePost();
    storeMetadata($post, ['robots' => 'noindex, follow']);

    expect(renderHead($post))->toContain('<meta name="robots" content="noindex, follow">');
});

it('renders the stored og image path as an absolute url', function (): void {
    $post = makePost();
    storeMetadata($post, ['og_image' => 'head-metadata/cover.png']);

    expect(renderHead($post))
        ->toContain('<meta property="og:image" content="http://localhost/storage/head-metadata/cover.png">');
});

it('renders the stored og type, og title and og description', function (): void {
    $post = makePost();
    storeMetadata($post, [
        'og_type' => 'article',
        'og_title' => ['en' => 'Og title'],
        'og_description' => ['en' => 'Og description'],
    ]);

    $html = renderHead($post);

    expect($html)->toContain('<meta property="og:type" content="article">')
        ->and($html)->toContain('<meta property="og:title" content="Og title">')
        ->and($html)->toContain('<meta property="og:description" content="Og description">');
});

it('renders the stored twitter card', function (): void {
    $post = makePost();
    storeMetadata($post, ['twitter_card' => 'summary_large_image']);

    expect(renderHead($post))->toContain('content="summary_large_image"');
});

it('emits nothing for fields that are neither stored nor defaulted', function (): void {
    $post = makePost(['title' => 'Only a title', 'body' => null]);

    $html = renderHead($post);

    expect($html)->toContain('<title>Only a title</title>')
        ->and($html)->not->toContain('name="description"')
        ->and($html)->not->toContain('name="robots"')
        ->and($html)->not->toContain('property="og:')
        ->and($html)->not->toContain('rel="canonical"');
});

it('accepts an array of defaults from the model', function (): void {
    $post = ArrayDefaultsPost::query()->create(['title' => 'Ignored']);

    $post->applyHead();
    $html = view('layout')->render();

    expect($html)->toContain('<title>From an array</title>')
        ->and($html)->toContain('<meta name="robots" content="nofollow">');
});
