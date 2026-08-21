<?php

use Arzcode\FilamentHead\Data\HeadDefaults;

it('defaults every property to null', function (): void {
    $defaults = new HeadDefaults;

    expect($defaults->title)->toBeNull()
        ->and($defaults->description)->toBeNull()
        ->and($defaults->ogTitle)->toBeNull()
        ->and($defaults->ogDescription)->toBeNull()
        ->and($defaults->ogImage)->toBeNull()
        ->and($defaults->ogType)->toBeNull()
        ->and($defaults->twitterCard)->toBeNull()
        ->and($defaults->canonicalUrl)->toBeNull()
        ->and($defaults->robots)->toBeNull();
});

it('builds from an array keyed by property name', function (): void {
    $values = [
        'title' => 'A title',
        'description' => 'A description',
        'ogTitle' => 'An og title',
        'ogDescription' => 'An og description',
        'ogImage' => 'https://example.com/image.png',
        'ogType' => 'article',
        'twitterCard' => 'summary_large_image',
        'canonicalUrl' => 'https://example.com/a',
        'robots' => 'noindex, follow',
    ];

    $defaults = HeadDefaults::fromArray($values);

    foreach ($values as $property => $value) {
        expect($defaults->{$property})->toBe($value);
    }
});

it('leaves absent array keys null', function (): void {
    $defaults = HeadDefaults::fromArray(['title' => 'Only a title']);

    expect($defaults->title)->toBe('Only a title')
        ->and($defaults->description)->toBeNull()
        ->and($defaults->robots)->toBeNull();
});

it('wraps an array and passes a DTO through untouched', function (): void {
    $wrapped = HeadDefaults::wrap(['title' => 'From an array']);
    $original = new HeadDefaults(title: 'Already a DTO');

    expect($wrapped->title)->toBe('From an array')
        ->and(HeadDefaults::wrap($original))->toBe($original);
});
