# Changelog

All notable changes to `filament-head` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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
