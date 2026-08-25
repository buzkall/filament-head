# Changelog

All notable changes to `filament-head` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- `->withoutSection()` on `HeadMetadataFields`, rendering the bare fields for containers
  that already frame and label their contents — a tab, a wizard step, a fieldset.
- `->heading()` to override the section heading.
- The Open Graph title and description helpers now name the value they would reuse, quoting this
  locale's title or meta description instead of only saying that one exists.

### Changed

- **Breaking.** `canonical_url` is now stored as JSON keyed by locale, like the other text
  fields, and renders inside the locale tabs. On a site that serves each locale from its own
  URL, one shared canonical pointed every locale at a single page and asked search engines to
  drop the rest. Existing installs need the column migrated from `string` to `json`.
- **Breaking.** Unlike every other translatable field, `canonical_url` does not fall back to
  another locale: a blank one emits no canonical rather than borrowing a wrong address.
  `HeadMetadata::translated()` takes a `$fallback` argument for this.
- **Breaking.** `HeadMetadataFields` extends `Group` rather than `Section`, so the section is
  now something it renders rather than something it is. `HeadMetadataFields::make('Heading')`
  becomes `HeadMetadataFields::make()->heading('Heading')`.

## [0.1.0]

### Added

- `HeadMetadataFields` section for Filament 5 resource forms, editing title, meta
  description, Open Graph title/description/image/type, Twitter card, canonical URL
  and robots per record.
- `HasHeadMetadata` trait: a `headMetadata` morphOne, overridable `headDefaults()`
  fallbacks and a single `applyHead()` call for the public site.
- Per-locale storage for the four text fields, rendered as tabs when more than one
  locale is configured, with a locale → fallback locale → first non-empty chain.
- Optional `FilamentHeadPlugin` for per-panel overrides of the locales, disk,
  directory and the two length hints.
- `->without()` to hide any of the optional fields.
- English, Spanish and Catalan translations.
