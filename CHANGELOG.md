# Changelog

All notable changes to `filament-head` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.1.1] - 2026-11-01

### Added

- `filament-head:install` asks, for each panel provider in the app, whether to register
  `FilamentHeadPlugin` there, and adds the plugin call and its import when you answer yes.

### Removed

- `filament-head:install` no longer asks you to star the repository on GitHub.
- The App and Player Twitter cards are no longer offered in the form. They need tags the form
  cannot set, so X showed no card. Values already stored keep rendering.

### Fixed

- A sharing image on a record with no title no longer renders empty `og:image:alt` and
  `twitter:image:alt` tags.
- A canonical URL from the model's `headDefaults()` now follows the host's `forceHttps` setting,
  which laravel/head defaults to `https`, instead of always keeping its scheme. An `http://`
  address typed in the panel is still kept as typed.

## [0.1.0] - 2026-11-01

First release.

### Added

- `HasHeadMetadata` trait: a `headMetadata` morphOne, overridable `headDefaults()` fallbacks —
  a `HeadDefaults` object or a plain array — and a single `applyHead()` call for the public site.
  It only calls into `laravel/head` for values that are filled, so the host's `Head::defaults()`
  stay in place. `applyHead()` also takes the record's `HeadDefaults` when the caller has already
  built them, so they are not computed twice.
- A blank Open Graph title or description reuses the stored title or meta description before the
  model's Open Graph default, as the form's hint says.
- `HeadMetadataFields`, one component for Filament 5 resource forms that edits title, meta
  description, Open Graph title/description/image/type, Twitter card, canonical URL and robots per
  record. It is bound to the relationship, so Create and Edit pages need no changes.
- The section starts collapsed. On Filament 5.8 and later its content is lazily loaded, rendering
  only once it is opened and scrolled into view. Stored values still load with the form, so saving
  without opening the section keeps them, and a validation error inside it loads and expands it.
  Field keys stay `headMetadata.*`. `->collapsed(false)` and `->lazyLoaded(false)` turn either off.
- `->withoutSection()` renders the bare fields for containers that already frame and label their
  contents — a tab, a wizard step, a fieldset. `->heading()` overrides the section heading.
- `->without()` hides any of the optional fields, and throws on title, description or an unknown
  name.
- Every field shows what the page uses today as its placeholder: the record's `headDefaults()`,
  the title and description the Open Graph fields fall back to, and "index, follow" for robots.
  Select placeholders are marked "(default)". The shared fields — image, type, card, robots —
  preview their defaults in the site's primary locale (`fallback_locale`, else
  `app.fallback_locale`) rather than the admin's.
- The Open Graph title and description hints quote the value they would reuse. The robots hint
  explains that a blank field keeps the defaults, while any option, "Index and follow links"
  included, overrides them.
- Character counters on title and description, on the label row, turning red past the limit.
  They are hints, never validation, and update as you type.
- Per-locale storage for title, description, Open Graph title and description, and canonical URL,
  rendered as tabs when more than one locale is configured. Values fall back from the active
  locale to the fallback locale to the first non-empty translation — except `canonical_url`, which
  never borrows another locale's address; `HeadMetadata::translated()` takes a `$fallback`
  argument for this.
- Optional `FilamentHeadPlugin` for per-panel overrides of the locales, disk, directory and the two
  length hints, plus `defaultOgImage()` for a sharing image the application draws itself. The form
  previews it above the upload. The closure gets the record as `$record`, `$model` or by any class
  or interface it satisfies, and runs at most once per request. Only `http(s)` and root-relative
  URLs are previewed.
- `filament-head:install` publishes the config and the migration, and offers to run it.
- English, Spanish and Catalan translations, including the content type and Twitter card options.
  An option this package has not translated yet is labelled by its case name.
