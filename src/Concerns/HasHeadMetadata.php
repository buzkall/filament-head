<?php

namespace Arzcode\FilamentHead\Concerns;

use Arzcode\FilamentHead\Data\HeadDefaults;
use Arzcode\FilamentHead\Models\HeadMetadata;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Laravel\Head\Enums\OgType;
use Laravel\Head\Enums\TwitterCard;
use Laravel\Head\Facades\Head;

/**
 * Gives a model an editable head metadata record and the one call that applies it.
 *
 * Every call into laravel/head lives in applyHead(), so the pre-1.0 package is
 * coupled to this trait alone.
 *
 * @property-read HeadMetadata|null $headMetadata
 */
trait HasHeadMetadata
{
    /**
     * @return MorphOne<HeadMetadata, $this>
     */
    public function headMetadata(): MorphOne
    {
        return $this->morphOne(HeadMetadata::class, 'model');
    }

    /**
     * Override in the model to provide fallbacks for the fields the admin left blank.
     *
     * @return HeadDefaults|array<string, string|null>
     */
    public function headDefaults(): HeadDefaults|array
    {
        return new HeadDefaults;
    }

    /**
     * Push this record's metadata onto the current request's head.
     *
     * Runtime calls layer on top of the host application's Head::defaults(), and
     * calling a method overrides that one field — so only filled values are sent.
     */
    public function applyHead(): void
    {
        $defaults = HeadDefaults::wrap($this->headDefaults());

        $meta = $this->headMetadata;

        $title = $this->resolveHeadValue($meta?->translated('title'), $defaults->title);
        $description = $this->resolveHeadValue($meta?->translated('description'), $defaults->description);
        $ogTitle = $this->resolveHeadValue($meta?->translated('og_title'), $defaults->ogTitle);
        $ogDescription = $this->resolveHeadValue($meta?->translated('og_description'), $defaults->ogDescription);
        $ogImage = $this->resolveHeadValue($meta?->ogImageUrl(), $defaults->ogImage);
        $ogType = $this->resolveHeadValue($meta?->og_type, $defaults->ogType);
        $twitterCard = $this->resolveHeadValue($meta?->twitter_card, $defaults->twitterCard);
        $canonicalUrl = $this->resolveHeadValue($meta?->canonical_url, $defaults->canonicalUrl);
        $robots = $this->resolveHeadValue($meta?->robots, $defaults->robots);

        if ($title !== null) {
            Head::title($title);
        }

        if ($description !== null) {
            Head::description($description);
        }

        $og = array_filter([
            'type' => $ogType === null ? null : OgType::tryFrom($ogType),
            'title' => $ogTitle,
            'description' => $ogDescription,
        ], fn (mixed $value): bool => $value !== null);

        if ($og !== []) {
            Head::og(...$og);
        }

        if ($ogImage !== null) {
            Head::ogImage($ogImage, alt: $title ?? '');
        }

        if ($twitterCard !== null && ($card = TwitterCard::tryFrom($twitterCard)) !== null) {
            Head::twitter(card: $card);
        }

        if ($canonicalUrl !== null) {
            Head::canonical($canonicalUrl, forceHttps: false);
        }

        if ($robots !== null) {
            Head::robots($robots);
        }
    }

    /**
     * The stored value when it holds something, otherwise the model default.
     */
    protected function resolveHeadValue(?string $stored, ?string $default): ?string
    {
        if (filled($stored)) {
            return $stored;
        }

        return filled($default) ? $default : null;
    }
}
