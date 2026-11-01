# Filament Head

[![Tests](https://img.shields.io/github/actions/workflow/status/buzkall/filament-head/tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/buzkall/filament-head/actions/workflows/tests.yml)
[![Quality](https://img.shields.io/github/actions/workflow/status/buzkall/filament-head/quality.yml?branch=main&label=quality&style=flat-square)](https://github.com/buzkall/filament-head/actions/workflows/quality.yml)

Edit the metadata that search engines and social networks read — title, meta description, Open
Graph, canonical URL, robots — **per record, from a Filament panel**, and apply it on the public
site with one call.

Built on [`laravel/head`](https://github.com/laravel/head), Laravel's first-party head management
package. This is its first Filament integration.

```php
// In your resource's form:
HeadMetadataFields::make()

// In your public controller:
$post->applyHead();
```

Translatable per locale, translated into English, Spanish and Catalan.

| Default — one locale | With `locales` configured |
|---|---|
| <img src="art/section.png" alt="The SEO and sharing section opened, showing title and meta description with character counters, Open Graph fields showing the values they reuse as greyed-out placeholders, and selects marked as defaults" width="420"> | <img src="art/locale-tabs.png" alt="The same section with EN and ES tabs around the five translatable fields, and the image, type, card and robots fields shared below them" width="420"> |
| Greyed out is what the page uses today: the record's defaults, and the title and description the Open Graph fields reuse. Counters sit on the label row. | One tab per locale for the five text fields; image, type, card and robots stay shared. |

<img src="art/collapsed.png" alt="An Edit Post form with title and body fields and the SEO and sharing section collapsed beneath them" width="560">

The section sits at the foot of the form, collapsed — and on Filament 5.8+ its fields aren't even
rendered until it is opened. See [lazy loading](#lazy-loading).

---

## Requirements

| | |
|---|---|
| PHP | 8.3+ |
| Laravel | 13.17+ |
| Filament | 5 (5.8+ for [lazy loading](#lazy-loading)) |
| `laravel/head` | 0.2 |

Laravel 13.17 is the floor because that is what `laravel/head` itself requires.

## Installation

```bash
composer require arzcode/filament-head
php artisan filament-head:install
```

The install command publishes the config file, publishes the migration that creates the
`head_metadata` table and offers to run it.

Your own tables are never touched — every record's metadata lives in one polymorphic table.

If you have not set up `laravel/head` yet, add its Blade directive to your public layout:

```blade
<head>
    @head
</head>
```

## Usage

Three steps, in the order you would do them.

### 1. Add the trait to the model

```php
use Arzcode\FilamentHead\Concerns\HasHeadMetadata;

class Post extends Model
{
    use HasHeadMetadata;
}
```

That gives the model a `headMetadata` relationship and an `applyHead()` method.

### 2. Add the section to the resource form

```php
use Arzcode\FilamentHead\Schemas\HeadMetadataFields;

public static function form(Schema $schema): Schema
{
    return $schema->components([
        TextInput::make('title'),
        RichEditor::make('body'),

        HeadMetadataFields::make(),
    ]);
}
```

It is a collapsible `Section`, full width, that drops in anywhere a form component fits. **No
changes to your Create or Edit page classes are needed** — the fields are bound to the
`headMetadata` relationship, which Filament persists along with the record.

The section starts **collapsed**: the metadata overrides something most records never need. Use
`->collapsed(false)` to start it open. On Filament 5.8 and later its content is also
[lazily loaded](#lazy-loading), so a closed section costs the edit page next to nothing.

Inside a tab, a wizard step or anything else that already frames and labels its contents, that
section is a box in a box. Drop it:

```php
Tab::make(__('SEO'))
    ->schema([HeadMetadataFields::make()->withoutSection()]),
```

`->withoutSection()` renders the bare fields — no heading, no description, no collapse toggle.
They always render open and with the page: `->collapsed()` and lazy loading need the section.
To keep the section but change what it says, use `->heading('Search engines')`.

### 3. Apply it on the public site

```php
public function show(Post $post): View
{
    $post->applyHead();

    return view('posts.show', ['post' => $post]);
}
```

`applyHead()` calls into `laravel/head` **only for values that are actually filled**. Everything
else keeps whatever your application's `Head::defaults()` set, so the package layers on top of
your site-wide metadata instead of blanking it.

## Fallbacks: `headDefaults()`

Admins will leave fields blank. Override `headDefaults()` to say what should be used when they
do — typically the record's own columns:

```php
use Arzcode\FilamentHead\Data\HeadDefaults;

public function headDefaults(): HeadDefaults
{
    return new HeadDefaults(
        title: $this->title,
        description: str($this->excerpt)->limit(160)->toString(),
        ogImage: $this->cover_url,
        ogType: 'article',
        canonicalUrl: route('posts.show', $this),
    );
}
```

A plain array works too, keyed by the same property names:

```php
public function headDefaults(): array
{
    return ['title' => $this->title, 'ogType' => 'article'];
}
```

The form shows these defaults as the fields' placeholders, so an admin sees what the page uses
before overriding it. A blank Open Graph title or description shows the title or meta description
it falls back to, and a blank robots field shows "index, follow".

The fields stored once — image, type, card, robots — preview their defaults in the site's primary
locale: `fallback_locale`, else `app.fallback_locale`, when the form offers it, else the first
locale it offers. An admin browsing the panel in another language still sees what the main site
shares.

A blank robots field and the "Index and follow links" option look alike but are not the same: blank
keeps the record's and the site's defaults, while choosing any option, that one included, always
overrides them. The field's hint says so.

Resolution is **per field**: a stored value wins, a blank stored value falls through to the
default, and a field with neither is not emitted at all.

## Translations

`title`, `description`, `og_title`, `og_description` and `canonical_url` are stored as JSON keyed
by locale. The remaining fields — image, type, card, robots — are stored once.

By default the form renders one untabbed set of fields for the application's current locale. List
more than one locale and it renders a tab per locale:

```php
// config/filament-head.php
'locales' => ['es', 'en', 'ca'],
```

```php
// or per resource
HeadMetadataFields::make()->locales(['es', 'en'])
```

At render time, `applyHead()` picks the value for the active locale, then the configured
`fallback_locale` (defaulting to `app.fallback_locale`), then the first non-empty translation.

**`canonical_url` is the exception: it never falls back.** On a site that serves each locale from
its own URL, borrowing another locale's canonical points that page at a different one and asks
search engines to drop it. A locale you leave blank simply emits no canonical, so whatever your
application's `Head::defaults()` set — usually a self-referencing canonical — stands.

No `spatie/laravel-translatable` dependency — these are plain `array` casts.

## Hiding fields

```php
HeadMetadataFields::make()->without(['twitter_card', 'robots'])
```

Any of `og_title`, `og_description`, `og_image`, `og_type`, `twitter_card`, `canonical_url`,
`robots` can be hidden. Title and description always render — passing either of them, or a name
that is not a field, throws a `LogicException` rather than quietly dropping the input.

## Lazy loading

Filament 5.8 added
[lazily loaded child schemas](https://filamentphp.com/docs/5.x/schemas/sections#lazy-loading-section-content):
a section's header renders with the page, and its content only once the section is expanded and
scrolled into view, in a request of its own. This package turns that on by default, because the
section is collapsed by default and its content is the expensive part of the form — every
placeholder runs your `headDefaults()` once per locale, and the image preview may run a
`defaultOgImage()` closure that draws a card.

What that means in practice:

- **Nothing is lost by not opening it.** Only the rendering is deferred. The stored values load with
  the form, so saving the record without ever opening the section writes them back unchanged.
- **Validation errors are not hidden.** If a field inside the section fails validation — say a
  stored canonical URL that is not a URL — Filament loads the content and expands the section to show
  the error.
- **It loads once.** After the first opening the content stays loaded for the life of the page.
- **Field names don't change.** The fields keep their usual state paths and keys, such as
  `headMetadata.title.en`, so `fillForm()` and `assertFormFieldExists()` work as before.

To render the fields with the page instead — for instance, if your browser tests read them without
opening the section — turn it off:

```php
HeadMetadataFields::make()->lazyLoaded(false)
```

It never applies to `->withoutSection()`, and on Filament versions before 5.8 the fields simply
render with the page. `HeadMetadataFields::supportsLazyLoading()` tells you which you have.

**Testing your own forms.** A Livewire test only sees what has been rendered, so assertions on the
section's HTML — a placeholder, a hint — need the content loaded first:

```php
livewire(EditPost::class, ['record' => $post->getKey()])
    ->call('loadDeferredSchema', 'form.headMetadata')
    ->call('$refresh') // the load answers with a partial render; this renders the page with it
    ->assertSeeHtml('placeholder="My post"');
```

**Keys.** Every lazy schema needs a key that is unique on the page. The section uses the
relationship's name, `headMetadata`, which is unique by nature: a second set of these fields on the
same form would write to the same relationship. Inside a repeater or builder, Filament prefixes it
with the item's key, and separate relation managers are separate Livewire components, so neither
collides.

## Configuration

`config/filament-head.php`:

| Key | Default | What it does |
|---|---|---|
| `disk` | `public` | Disk the sharing image is uploaded to. |
| `directory` | `head-metadata` | Directory within that disk. |
| `locales` | `null` | Locales offered in the form. `null` uses the active locale alone, untabbed. |
| `fallback_locale` | `null` | Locale used when the active one is blank. `null` uses `app.fallback_locale`. |
| `title_limit` | `60` | Recommended title length, shown as a counter. |
| `description_limit` | `160` | Recommended description length, shown as a counter. |

The limits are **hints, never validation** — a longer title still saves.

## The plugin (optional)

Registering the plugin is only needed to give one panel different settings from the config file:

```php
use Arzcode\FilamentHead\FilamentHeadPlugin;

$panel->plugin(
    FilamentHeadPlugin::make()
        ->locales(['es', 'en'])
        ->disk('media')
        ->directory('seo')
        ->titleLimit(55)
        ->descriptionLimit(150)
        ->defaultOgImage(fn (Post $record): ?string => $record->share_card_url)
);
```

| Fluent method | Config key |
|---|---|
| `locales(array\|Closure)` | `locales` |
| `disk(string\|Closure)` | `disk` |
| `directory(string\|Closure)` | `directory` |
| `titleLimit(int\|Closure)` | `title_limit` |
| `descriptionLimit(int\|Closure)` | `description_limit` |

`defaultOgImage()` has no config key. It is for a sharing image the application draws or stores
itself, which the model's `headDefaults()` cannot name. The form shows it as a preview above the
upload, only when the model's own `ogImage` default is blank. Like the rest of the plugin,
`applyHead()` never reads it, so the public page has to keep setting that image itself.

The closure gets the record as `$record` or `$model`, or by any type it satisfies: its class, a
parent class such as `Model`, or an interface it implements. It runs at most once per request, and
only once the section is open. Return an `http(s)` or root-relative URL. Anything else, such as a
`javascript:` or `data:` URL, gets no preview, because escaping alone does not make that kind of
link safe.

Precedence, highest first: `HeadMetadataFields::make()->locales(...)` → the plugin → the config
file.

**`applyHead()` never reads the plugin.** Your public site runs outside any panel, so there is no
plugin to read — the disk and fallback locale it uses always come from the config file. Keep them
consistent if you override the disk on a panel.

## FAQ

**Does it work without registering the plugin?**
Yes. The plugin only carries per-panel overrides. Add the trait and the form section and
everything works from `config/filament-head.php`.

**Which Laravel version?**
13.17 and up, because `laravel/head` requires it. There is no Laravel 12 build.

**Does it render anything itself?**
No. Rendering is entirely `laravel/head`'s `@head` directive in your layout. This package only
stores values and pushes them onto the current request.

**What happens to my `Head::defaults()`?**
They stay. `applyHead()` only calls the methods for fields that have a value, so an unset field
inherits your app-level default rather than being blanked.

**Can I use it on a model that is not exposed in a resource?**
Yes — the trait and `applyHead()` work with no panel involved. The Filament section is just the
editing UI.

**Why is the canonical URL per locale when robots and the image are not?**
Because it is the only field where a shared value is actively wrong. Robots directives and a
sharing image are the same whatever language the page is in; a canonical URL is an address, and
each locale has its own.

**Is the canonical URL forced to HTTPS?**
No. Stored URLs are passed through with `forceHttps: false`, so an external or plain-`http`
canonical is emitted exactly as you typed it.

## Testing

```bash
composer test      # Pest
composer analyse   # Larastan, level 6
composer format    # Pint
```

## Trying it in one of your own projects

No need to publish anything. Point the project's `composer.json` at your local checkout with a
[path repository](https://getcomposer.org/doc/05-repositories.md#path):

```jsonc
"repositories": [
    {
        "type": "path",
        "url": "../filament-head",              // wherever you cloned it
        "options": { "symlink": true }          // true = edits apply instantly
    }
],
```

Then require it as a dev version and install as usual:

```bash
composer require arzcode/filament-head:@dev
php artisan filament-head:install
```

Remember to drop the `repositories` entry before deploying that project.

## Contributing

Pull requests are welcome. Please keep `composer test`, `composer analyse` and
`composer format --test` green, and add any new user-facing string to all three language files —
a test asserts they carry identical keys.

`laravel/head` is pre-1.0, so every call into it is deliberately confined to
`HasHeadMetadata::applyHead()`. Keep it that way: one method to update when its API moves.

## License

MIT. See [LICENSE](LICENSE).
