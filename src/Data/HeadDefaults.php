<?php

namespace Arzcode\FilamentHead\Data;

/**
 * Fallbacks a model supplies for the fields an admin left blank.
 */
final readonly class HeadDefaults
{
    public function __construct(
        public ?string $title = null,
        public ?string $description = null,
        public ?string $ogTitle = null,
        public ?string $ogDescription = null,
        /** Absolute URL. */
        public ?string $ogImage = null,
        /** A Laravel\Head\Enums\OgType value. */
        public ?string $ogType = null,
        /** A Laravel\Head\Enums\TwitterCard value. */
        public ?string $twitterCard = null,
        public ?string $canonicalUrl = null,
        public ?string $robots = null,
    ) {}

    /**
     * Normalize what a model returned from headDefaults().
     *
     * @param  self|array<string, string|null>  $defaults
     */
    public static function wrap(self|array $defaults): self
    {
        return $defaults instanceof self ? $defaults : self::fromArray($defaults);
    }

    /**
     * @param  array<string, string|null>  $values  keyed by property name
     */
    public static function fromArray(array $values): self
    {
        return new self(
            title: $values['title'] ?? null,
            description: $values['description'] ?? null,
            ogTitle: $values['ogTitle'] ?? null,
            ogDescription: $values['ogDescription'] ?? null,
            ogImage: $values['ogImage'] ?? null,
            ogType: $values['ogType'] ?? null,
            twitterCard: $values['twitterCard'] ?? null,
            canonicalUrl: $values['canonicalUrl'] ?? null,
            robots: $values['robots'] ?? null,
        );
    }
}
