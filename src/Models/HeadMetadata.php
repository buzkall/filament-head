<?php

namespace Arzcode\FilamentHead\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

/**
 * @property array<string, string|null>|null $title
 * @property array<string, string|null>|null $description
 * @property array<string, string|null>|null $og_title
 * @property array<string, string|null>|null $og_description
 * @property string|null $og_image
 * @property string|null $og_type
 * @property string|null $twitter_card
 * @property string|null $canonical_url
 * @property string|null $robots
 */
class HeadMetadata extends Model
{
    protected $table = 'head_metadata';

    /** @var list<string> */
    protected $fillable = [
        'title',
        'description',
        'og_title',
        'og_description',
        'og_image',
        'og_type',
        'twitter_card',
        'canonical_url',
        'robots',
    ];

    /**
     * The record this metadata describes.
     *
     * @return MorphTo<Model, $this>
     */
    public function model(): MorphTo
    {
        return $this->morphTo('model');
    }

    /**
     * Resolve a translatable column for a locale, falling back to the configured
     * fallback locale and finally to the first non-empty translation.
     */
    public function translated(string $column, ?string $locale = null): ?string
    {
        /** @var array<string, string|null> $values */
        $values = $this->{$column} ?? [];
        $locale ??= app()->getLocale();
        $fallback = config('filament-head.fallback_locale') ?? config('app.fallback_locale');

        if (filled($values[$locale] ?? null)) {
            return $values[$locale];
        }

        if (is_string($fallback) && filled($values[$fallback] ?? null)) {
            return $values[$fallback];
        }

        return collect($values)->first(fn (?string $value): bool => filled($value));
    }

    /**
     * Absolute URL of the uploaded og:image, if there is one.
     */
    public function ogImageUrl(): ?string
    {
        if (blank($this->og_image)) {
            return null;
        }

        return Storage::disk(config('filament-head.disk', 'public'))->url($this->og_image);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'title' => 'array',
            'description' => 'array',
            'og_title' => 'array',
            'og_description' => 'array',
        ];
    }
}
