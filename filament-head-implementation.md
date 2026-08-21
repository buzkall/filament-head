# Build `filament-head` — implementation plan

> Hand this file to a fresh Claude Code session. It is self-contained: every design decision is
> already made — implement, don't re-decide. Strategic background lives in
> `plans/filament-head.md` (same folder); you don't need it to build.

## Mission

Create a new open-source Filament v5 plugin that lets admins edit `laravel/head` metadata
(title, meta description, Open Graph, canonical, robots) per Eloquent record from a Filament
panel, and apply it on the public site with one call. First Filament integration for Laravel's
first-party `laravel/head` package — no competitor exists.

- **Location**: create the project at `~/Code/arzcode/filament-head`
- **Packagist name**: `arzcode/filament-head` · **GitHub**: `buzkall/filament-head`
- **Namespace**: `Arzcode\FilamentHead` (tests: `Arzcode\FilamentHead\Tests`)
- **License**: MIT

## Reference implementation — copy its conventions

`~/Code/arzcode/filament-magic-login` is the same author's Filament plugin. **Read it first.**
Copy and adapt (rename `magic-login` → `head` everywhere):

- `composer.json` structure (scripts, config, extra.laravel.providers, support block)
- `.github/workflows/tests.yml` and `.github/workflows/quality.yml`
- `pint.json`, `phpstan.neon` (or equivalents it uses), `.gitattributes`, `.gitignore`, `LICENSE`
- `tests/TestCase.php` — the full Filament provider bootstrapping for orchestra/testbench
- `tests/Fixtures/Panels/*` — how panel fixtures are declared
- `src/FilamentMagicLoginServiceProvider.php` — the `PackageServiceProvider` + install-command
  pattern
- `src/MagicLoginPlugin.php` — the `Plugin` contract + `EvaluatesClosures` fluent-config pattern
- README structure and the `art/` screenshot convention; `resources/lang/{en,es,ca}` translations

Follow the author's code style: PHP 8.3+, constructor promotion, explicit return types, PHPDoc
over inline comments, `new Foo` without parens when argument-less, curly braces always.

## Dependency facts (verified)

- `laravel/head` is at **v0.2.1** and requires `illuminate/*: ^13.17` → this package supports
  **Laravel 13.17+ only**. Pin `"laravel/head": "^0.2"` — it is pre-1.0, so isolate every call
  to its API inside `HasHeadMetadata::applyHead()` (one coupling point).
- `composer.json` require: `php: ^8.3`, `filament/filament: ^5.0`, `laravel/head: ^0.2`,
  `illuminate/contracts: ^13.17`, `spatie/laravel-package-tools: ^1.92`.
- require-dev: same list as magic-login (pest ^5, testbench `^11.0`, larastan ^3, pint,
  pest-plugin-livewire ^5, phpstan extension-installer + plugins). Testbench must be a version
  that supports Laravel 13 — check `composer why-not` if ^11 doesn't resolve; use what resolves.
- **No spatie/laravel-translatable dependency** — translations use plain `array` casts (below).

## `laravel/head` API cheatsheet (all that this package touches)

```php
use Laravel\Head\Facades\Head;
use Laravel\Head\Enums\OgType;        // OgType::Website, OgType::Article
use Laravel\Head\Enums\TwitterCard;   // TwitterCard::Summary, TwitterCard::SummaryWithLargeImage

Head::title('About');                          // gets app-level suffix applied
Head::title('Exact thing', exact: true);       // ignores suffix
Head::description('...');
Head::canonical('/about', forceHttps: false);  // ALWAYS pass forceHttps: false for stored URLs
Head::robots('noindex, follow');
Head::og(type: OgType::Article);
Head::ogImage('https://...', alt: '...');
Head::twitter(card: TwitterCard::SummaryWithLargeImage);
```

Rendering happens via the `@head` Blade directive in the host app's layout — the package never
renders anything itself. Runtime calls layer on top of the host's `Head::defaults()`; calling a
method only overrides that one field. Therefore: **`applyHead()` must only call methods for
values that are actually filled** — never pass null/empty to "reset" anything.

## Package layout to produce

```
filament-head/
├── composer.json
├── config/filament-head.php
├── database/migrations/create_head_metadata_table.php.stub
├── resources/lang/en/filament-head.php        (+ es, ca — translate all keys)
├── src/
│   ├── FilamentHeadServiceProvider.php
│   ├── FilamentHeadPlugin.php
│   ├── Models/HeadMetadata.php
│   ├── Concerns/HasHeadMetadata.php
│   ├── Data/HeadDefaults.php
│   └── Schemas/HeadMetadataFields.php
└── tests/
    ├── TestCase.php
    ├── Pest.php
    ├── Fixtures/
    │   ├── Models/Post.php
    │   ├── Panels/AdminPanelProvider.php
    │   ├── Resources/PostResource.php (+ Pages/{CreatePost,EditPost,ListPosts}.php)
    │   ├── database/migrations/…create_users_table.php + …create_posts_table.php
    │   └── views/layout.blade.php             (contains @head, for rendering tests)
    ├── Feature/{FormPersistenceTest,ApplyHeadRenderingTest,LocaleTabsTest,InstallCommandTest}.php
    └── Unit/{HeadDefaultsTest,HeadMetadataModelTest,TranslationsTest}.php
```

## File-by-file specification

### 1. `config/filament-head.php`

```php
return [
    // Disk + directory where og:image uploads are stored.
    'disk' => env('FILAMENT_HEAD_DISK', 'public'),
    'directory' => 'head-metadata',

    // Locales offered in the form. null => [app()->getLocale()] (no tabs rendered).
    'locales' => null,

    // Locale used when the active locale has no value. null => config('app.fallback_locale').
    'fallback_locale' => null,

    // Recommended lengths, shown as counters in the form (hints, never validation).
    'title_limit' => 60,
    'description_limit' => 160,
];
```

### 2. Migration stub `create_head_metadata_table.php.stub`

Anonymous class migration (magic-login stub style). Table `head_metadata`:

- `id`
- `morphs('model')` + unique index on `['model_type', 'model_id']`
- `json('title')->nullable()` — shape `['es' => '...', 'en' => '...']`
- `json('description')->nullable()`
- `json('og_title')->nullable()`
- `json('og_description')->nullable()`
- `string('og_image')->nullable()` — relative path on the configured disk
- `string('og_type')->nullable()` — a `Laravel\Head\Enums\OgType` value
- `string('twitter_card')->nullable()` — a `Laravel\Head\Enums\TwitterCard` value
- `string('canonical_url')->nullable()`
- `string('robots')->nullable()`
- `timestamps()`

### 3. `Models/HeadMetadata.php`

- `$fillable`: every column above.
- `casts()`: `title`, `description`, `og_title`, `og_description` → `'array'`.
- `morphTo('model')` relationship.
- Locale helper (the only translation machinery in the package):

```php
/**
 * Resolve a translatable column for a locale, falling back to the configured
 * fallback locale and finally to the first non-empty translation.
 */
public function translated(string $column, ?string $locale = null): ?string
{
    $values = $this->{$column} ?? [];
    $locale ??= app()->getLocale();
    $fallback = config('filament-head.fallback_locale') ?? config('app.fallback_locale');

    return filled($values[$locale] ?? null) ? $values[$locale]
        : (filled($values[$fallback] ?? null) ? $values[$fallback]
        : collect($values)->first(fn ($value) => filled($value)));
}
```

- `ogImageUrl(): ?string` — `$this->og_image ? Storage::disk(config('filament-head.disk'))->url($this->og_image) : null`.

### 4. `Data/HeadDefaults.php`

Readonly DTO for model-provided fallbacks:

```php
final readonly class HeadDefaults
{
    public function __construct(
        public ?string $title = null,
        public ?string $description = null,
        public ?string $ogTitle = null,
        public ?string $ogDescription = null,
        public ?string $ogImage = null,       // absolute URL
        public ?string $ogType = null,        // OgType value string
        public ?string $twitterCard = null,   // TwitterCard value string
        public ?string $canonicalUrl = null,
        public ?string $robots = null,
    ) {}

    /** @param array<string, string|null> $values keys = property names */
    public static function fromArray(array $values): self { /* named-arg spread */ }
}
```

### 5. `Concerns/HasHeadMetadata.php` — the core

```php
trait HasHeadMetadata
{
    public function headMetadata(): MorphOne
    {
        return $this->morphOne(HeadMetadata::class, 'model');
    }

    /** Override in the model to provide fallbacks for fields the admin left blank. */
    public function headDefaults(): HeadDefaults|array
    {
        return new HeadDefaults;
    }

    public function applyHead(): void { /* see contract below */ }
}
```

`applyHead()` contract (implement exactly):

1. Normalize `headDefaults()` to a `HeadDefaults` (via `fromArray` when an array is returned).
2. Load `$this->headMetadata` (may be null — every stored value is then null).
3. Resolve each value as **stored ?: default** where stored translatable values go through
   `$meta->translated($column)` and `og_image` through `$meta->ogImageUrl()`.
4. Call the `Head::` facade **only for filled values**:
   - `Head::title($title)` (no `exact` — let the host app's suffix apply)
   - `Head::description($description)`
   - `Head::og(type: OgType::from($ogType))` when og_type filled;
     `Head::og(title: ...)` / `og(description: ...)` for filled og-title/og-description
     (combine into a single `og(...)` call with only the filled named args — build an
     argument array and spread it)
   - `Head::ogImage($url, alt: $title ?? '')` when an image resolved
   - `Head::twitter(card: TwitterCard::from($twitterCard))` when filled
   - `Head::canonical($canonicalUrl, forceHttps: false)` when filled
   - `Head::robots($robots)` when filled
5. Never call `Head::og(siteName: ...)` or touch alternates/favicon — site identity belongs to
   the host app's `Head::defaults()`.

### 6. `Schemas/HeadMetadataFields.php` — the Filament component

A class **extending `Filament\Schemas\Components\Section`** so it drops into any resource
`form()` and supports fluent configuration. Filament v5 namespaces (do not use others):
form fields from `Filament\Forms\Components\*`, layout from `Filament\Schemas\Components\*`,
utilities from `Filament\Schemas\Components\Utilities\*`.

```php
HeadMetadataFields::make()          // Section titled __('filament-head::filament-head.section')
    ->locales(['es', 'en'])         // optional; default = resolved config locales
    ->without(['twitter_card'])     // optional; hide any of: og_title, og_description,
                                    //   og_image, og_type, twitter_card, canonical_url, robots
```

Implementation notes:

- `public static function make(...$args): static` builds the Section; `setUp()` configures:
  `->collapsible()`, `->columnSpanFull()`, and `->schema(fn () => $this->buildSchema())` so the
  schema is built lazily *after* `locales()`/`without()` ran.
- Inside `buildSchema()`: one `Group::make($fields)->relationship('headMetadata')` — this is what
  makes Create/Edit pages persist the morphOne automatically with **zero page-class changes**.
- Locale resolution: `$this->locales` if set, else `config('filament-head.locales')`, else
  `[app()->getLocale()]`.
- **Single locale** → plain fields named `title.{locale}` etc. (dotted paths write into the
  array casts). **Multiple locales** → `Tabs` with one tab per locale (label `strtoupper`),
  each containing the four translatable fields for that locale.
- Fields (all `->label()`s via `__('filament-head::filament-head.*')` keys):
  - `TextInput::make("title.{$locale}")` — `->live(onBlur: true)` and a character counter as
    `->helperText()`: "`{strlen}/{limit}`" using `config('filament-head.title_limit')`; append
    a warning phrase when over. Hints only — no validation rules.
  - `Textarea::make("description.{$locale}")->rows(2)` — same counter against
    `description_limit`.
  - `TextInput::make("og_title.{$locale}")` + `Textarea::make("og_description.{$locale}")` —
    helper text "falls back to title/description".
- Non-translatable fields (below the tabs, inside the same relationship Group):
  - `FileUpload::make('og_image')->image()->disk(config('filament-head.disk'))
    ->directory(config('filament-head.directory'))->visibility('public')`
  - `Select::make('og_type')->options(...)` from `OgType` cases (value => label), nullable
  - `Select::make('twitter_card')->options(...)` from `TwitterCard` cases, nullable
  - `TextInput::make('canonical_url')->url()->nullable()`
  - `Select::make('robots')->options([...])->nullable()` with options:
    `'all'`, `'noindex, follow'`, `'noindex, nofollow'`, `'nofollow'`
- `->without()` filters the built field list by column name before rendering.
- Empty-state behavior: Filament creates the related row on save; an entirely-empty form must
  NOT create a junk row — use `Group::relationship()`'s standard behavior; if it creates a row
  of nulls, that's acceptable for v0.1 (applyHead treats nulls as unset). Do not build custom
  pruning logic.

### 7. `FilamentHeadPlugin.php`

Thin `Filament\Contracts\Plugin` (ID `'filament-head'`) using `EvaluatesClosures`, mirroring
`MagicLoginPlugin`'s fluent pattern: `->locales()`, `->disk()`, `->directory()`,
`->titleLimit()`, `->descriptionLimit()`. In `register()`/`boot()`: nothing to register —
the plugin only carries panel-level config. `HeadMetadataFields` checks
`filament()->hasPlugin('filament-head')` and prefers plugin values over the config file.
**`applyHead()` never reads the plugin** (public site has no panel) — config file only.
Document this split in the README.

### 8. `FilamentHeadServiceProvider.php`

```php
class FilamentHeadServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('filament-head')
            ->hasConfigFile()
            ->hasMigration('create_head_metadata_table')
            ->hasTranslations()
            ->hasInstallCommand(function (InstallCommand $command): void {
                $command
                    ->publishConfigFile()
                    ->publishMigrations()
                    ->askToRunMigrations()
                    ->askToStarRepoOnGitHub('buzkall/filament-head');
            });
    }
}
```

### 9. Translations `resources/lang/{en,es,ca}/filament-head.php`

Keys for: section heading ("SEO & sharing" / "SEO y compartición" / "SEO i compartició"),
every field label, the counter/fallback helper texts, robots option labels. English is the
source; provide real Spanish and Catalan translations (author is a native Spanish speaker —
write natural es/ca, not machine-literal).

## Test fixtures

- `Fixtures/Models/Post.php` — `HasHeadMetadata`; columns `title`, `body`;
  `headDefaults()` returns `new HeadDefaults(title: $this->title, description: str($this->body)->limit(160)->toString())`.
- `Fixtures/Resources/PostResource.php` — minimal Filament v5 resource whose `form()` contains
  `TextInput::make('title')` and `HeadMetadataFields::make()`; standard List/Create/Edit pages.
- `Fixtures/Panels/AdminPanelProvider.php` — panel `admin`, registers the resource, login not
  needed (`->authGuard` default; tests use `actingAs`).
- `Fixtures/views/layout.blade.php` — minimal HTML with `@head` in `<head>`; a test route
  (defined in `TestCase` or per-test) does `$post->applyHead(); return view('layout');`.
- `TestCase.php`: copy magic-login's provider list + add `Laravel\Head\HeadServiceProvider`
  and this package's provider; sqlite in-memory; load fixture migrations + the package stub
  migration.

## Tests (Pest 5, `it()` style, `actingAs` for panel tests)

**Feature/FormPersistenceTest**
1. Edit page fill `['headMetadata.title.en' => 'Custom', 'headMetadata.description.en' => 'Desc']`
   *(adjust state paths to what the relationship Group actually produces — verify with
   `->assertFormFieldExists()` first)*, save, `assertHasNoFormErrors`; assert `head_metadata`
   row exists with `model_type` = Post, `title['en'] === 'Custom'`.
2. Create page: record + metadata created together.
3. `->without(['robots'])` hides the robots field (`assertFormFieldDoesNotExist`).

**Feature/ApplyHeadRenderingTest** (rendering through the `@head` layout route)
1. Stored title + description render as `<title>` / `<meta name="description">`.
2. No stored row → `headDefaults()` values render.
3. Stored value beats default; blank stored field falls back to default per-field.
4. Stored canonical `http://external.example/x` renders unchanged (forceHttps: false).
5. Stored robots renders `<meta name="robots" content="noindex, follow">`.
6. og_image path renders as absolute URL `<meta property="og:image" ...>`.
7. No filled value anywhere → no description tag rendered at all (proves "only filled" rule).

**Feature/LocaleTabsTest**
1. `config(['filament-head.locales' => ['es', 'en']])` → tabs render, both locales save into
   the json column.
2. `applyHead()` with `app()->setLocale('en')` uses the `en` value; missing `en` falls back to
   fallback locale.
3. Single locale (default) → no Tabs component in the schema.

**Feature/InstallCommandTest** — `filament-head:install` publishes config + migration
(mirror magic-login's InstallCommandTest).

**Unit** — `HeadDefaultsTest` (fromArray round-trip), `HeadMetadataModelTest`
(`translated()` fallback chain: locale → fallback_locale → first non-empty → null;
`ogImageUrl()` null-safe), `TranslationsTest` (es/ca files contain every en key — copy
magic-login's test).

## Build order

1. `mkdir ~/Code/arzcode/filament-head && cd` there; `git init`.
2. Copy skeleton files from magic-login (workflows, pint/phpstan config, .gitattributes,
   LICENSE); write `composer.json`; `composer install`.
3. Config file, migration stub, `HeadMetadata` model, `HeadDefaults` — plus Unit tests → green.
4. `HasHeadMetadata::applyHead()` + layout fixture + ApplyHeadRenderingTest → green.
5. `HeadMetadataFields` + fixtures resource/panel + FormPersistenceTest + LocaleTabsTest → green.
   ⚠️ The relationship-Group state paths and the empty-row behavior are the risky part —
   verify with real Livewire tests early, adapt the spec's state paths to reality if needed.
6. Plugin class + provider install command + InstallCommandTest → green.
7. Translations es/ca + TranslationsTest.
8. `vendor/bin/pint`, `vendor/bin/phpstan analyse` → clean. Full `vendor/bin/pest` → green.
9. README.md: badges, install (`composer require arzcode/filament-head` +
   `php artisan filament-head:install`), the three-step usage (trait → component → applyHead),
   config reference, plugin reference, FAQ ("works without the plugin?", "Laravel version?").
   Follow magic-login's README structure and tone.
10. Initial commit. Do NOT create the GitHub repo, push, or tag — the author does that.

## Definition of done

- `vendor/bin/pest` fully green; `vendor/bin/pint --test` clean; phpstan clean at the level
  magic-login uses.
- A fresh Laravel 13 app following only the README reaches: editable SEO section in a resource,
  metadata rendered through `@head`.
- No decision points left open for the author except: GitHub repo creation, Packagist submit,
  screenshots for `art/`.

## Out of scope (do not build)

- SERP live-preview field (v0.2), robots free-text, JSON-LD schema fields, route-level metadata
  for non-model pages, global-defaults settings page, sitemap generation, spatie/laravel-
  translatable integration, Laravel 12 support.
