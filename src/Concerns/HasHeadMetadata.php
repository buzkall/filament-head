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
     *
     * A blank Open Graph title or description reuses the stored title or meta
     * description before the model's Open Graph default, as the form promises:
     * otherwise a title typed in the panel would change <title> while the share
     * preview kept the derived one, whenever the host had already set og:title.
     *
     * @param  HeadDefaults|array<string, string|null>|null  $defaults  the model's
     *                                                                  headDefaults(), when the caller already built them
     */
    public function applyHead(HeadDefaults|array|null $defaults = null): void
    {
        $defaults = HeadDefaults::wrap($defaults ?? $this->headDefaults());

        $meta = $this->headMetadata;

        $storedTitle = $meta?->translated('title');
        $storedDescription = $meta?->translated('description');

        $title = $this->resolveHeadValue($storedTitle, $defaults->title);
        $description = $this->resolveHeadValue($storedDescription, $defaults->description);
        $ogTitle = $this->resolveHeadValue($meta?->translated('og_title'), $this->resolveHeadValue($storedTitle, $defaults->ogTitle));
        $ogDescription = $this->resolveHeadValue($meta?->translated('og_description'), $this->resolveHeadValue($storedDescription, $defaults->ogDescription));
        $ogImage = $this->resolveHeadValue($meta?->ogImageUrl(), $defaults->ogImage);
        $ogType = $this->resolveHeadValue($meta?->og_type, $defaults->ogType);
        $twitterCard = $this->resolveHeadValue($meta?->twitter_card, $defaults->twitterCard);
        // No cross-locale fallback: borrowing another locale's canonical would point
        // this page at that one and drop it from the index.
        $canonicalUrl = $this->resolveHeadValue($meta?->translated('canonical_url', fallback: false), $defaults->canonicalUrl);
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
