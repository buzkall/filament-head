<?php

namespace Arzcode\FilamentHead\Schemas;

use Arzcode\FilamentHead\Data\HeadDefaults;
use Arzcode\FilamentHead\FilamentHeadPlugin;
use Arzcode\FilamentHead\Support\RenderedHtml;
use Closure;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Traits\Localizable;
use Laravel\Head\Enums\OgType;
use Laravel\Head\Enums\TwitterCard;
use LogicException;

/**
 * The whole editing UI, as one component that drops into any resource form.
 *
 * By default it renders as a collapsed Section with its own heading, whose content
 * is lazily loaded on Filament 5.8+ — see lazyLoaded(). Inside a tab
 * — or any container that already labels and frames its contents — call
 * ->withoutSection() to render the bare fields instead.
 *
 * The fields are wrapped in a Group bound to the `headMetadata` relationship, so
 * Create and Edit pages persist the morphOne without any page-class changes.
 *
 * Every field shows the record's headDefaults() as its placeholder, so an admin
 * sees what the page says today before typing over it.
 */
class HeadMetadataFields extends Group
{
    use Localizable;

    /** The morphOne HasHeadMetadata adds, which the fields are bound to. */
    public const RELATIONSHIP = 'headMetadata';

    /** Schemes the sharing image preview may link to. */
    protected const PREVIEW_SCHEMES = ['http', 'https'];

    /** Columns of head_metadata that ->without() may hide. */
    public const OPTIONAL_FIELDS = [
        'og_title',
        'og_description',
        'og_image',
        'og_type',
        'twitter_card',
        'canonical_url',
        'robots',
    ];

    /** Columns of head_metadata stored as JSON keyed by locale. */
    public const TRANSLATABLE_FIELDS = [
        'title',
        'description',
        'og_title',
        'og_description',
        'canonical_url',
    ];

    /** @var array<int, string>|null */
    protected ?array $localeOverride = null;

    /** @var array<int, string> */
    protected array $hiddenFields = [];

    protected string|Htmlable|Closure|null $heading = null;

    protected bool $sectioned = true;

    protected bool|Closure $isCollapsed = true;

    protected bool|Closure $isLazyLoaded = true;

    /** @var array<string, HeadDefaults> keyed by locale */
    protected array $resolvedDefaults = [];

    protected ?string $resolvedOgImage = null;

    protected bool $hasResolvedOgImage = false;

    /**
     * Heading of the wrapping section. Defaults to the packaged one, and is
     * ignored once ->withoutSection() has run.
     */
    public function heading(string|Htmlable|Closure|null $heading): static
    {
        $this->heading = $heading;

        return $this;
    }

    /**
     * Render the bare fields, with no section heading, description or collapse
     * toggle. Use it wherever the container already supplies those — a tab, a
     * wizard step, a fieldset — where a second frame is just a box in a box.
     */
    public function withoutSection(bool $condition = true): static
    {
        $this->sectioned = ! $condition;

        return $this;
    }

    /**
     * Whether the section starts collapsed. It does by default: the metadata is an
     * override most records never need, and on Filament 5.8+ a collapsed section's
     * fields are not even rendered until it is opened. No effect without a section:
     * the bare fields always render open.
     */
    public function collapsed(bool|Closure $condition = true): static
    {
        $this->isCollapsed = $condition;

        return $this;
    }

    /**
     * Whether the section's content waits until it is opened and scrolled into
     * view before rendering. It does by default, on Filament 5.8+. The state is
     * still loaded with the form, so saving without opening it keeps the stored
     * values, and a validation error inside it loads and opens it. No effect
     * without a section, or on Filament versions without lazily loaded schemas.
     */
    public function lazyLoaded(bool|Closure $condition = true): static
    {
        $this->isLazyLoaded = $condition;

        return $this;
    }

    public function isLazyLoaded(): bool
    {
        return $this->sectioned
            && static::supportsLazyLoading()
            && (bool) $this->evaluate($this->isLazyLoaded);
    }

    /**
     * Filament 5.8 added lazily loaded child schemas as deferLoading(), later
     * renamed lazyLoaded(). Older versions have neither.
     */
    public static function supportsLazyLoading(): bool
    {
        // Always true against the Filament this package is analysed with; not on every version it supports.
        return method_exists(Schema::class, 'lazyLoaded') // @phpstan-ignore function.alreadyNarrowedType
            || method_exists(Schema::class, 'deferLoading'); // @phpstan-ignore function.alreadyNarrowedType
    }

    /**
     * Locales to offer, overriding the plugin and the config file.
     *
     * @param  array<int, string>  $locales
     */
    public function locales(array $locales): static
    {
        $this->localeOverride = array_values($locales);

        return $this;
    }

    /**
     * Hide fields by column name. Only OPTIONAL_FIELDS may be hidden: title and
     * description are the point of the section, so hiding them is a typo, not a choice.
     *
     * @param  array<int, string>  $fields
     *
     * @throws LogicException when a name is not one of OPTIONAL_FIELDS
     */
    public function without(array $fields): static
    {
        $unknown = array_diff($fields, static::OPTIONAL_FIELDS);

        if ($unknown !== []) {
            throw new LogicException(__('filament-head::filament-head.exceptions.unknown_field', [
                'fields' => implode(', ', $unknown),
                'allowed' => implode(', ', static::OPTIONAL_FIELDS),
            ]));
        }

        $this->hiddenFields = array_values($fields);

        return $this;
    }

    /**
     * @return array<int, string>
     */
    public function getLocales(): array
    {
        /** @var array<int, string>|null $locales */
        $locales = $this->localeOverride
            ?? FilamentHeadPlugin::get()?->getLocales()
            ?? config('filament-head.locales');

        return filled($locales) ? array_values($locales) : [app()->getLocale()];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->columnSpanFull()
            // Built lazily, so ->locales(), ->without() and ->withoutSection() have already run.
            ->schema(fn (): array => $this->buildSchema());
    }

    /**
     * @return array<int, Component>
     */
    protected function buildSchema(): array
    {
        $fields = Group::make([
            ...$this->translatableFields(),
            ...$this->plainFields(),
        ])->relationship(static::RELATIONSHIP);

        if (! $this->sectioned) {
            return [$fields];
        }

        if ($this->isLazyLoaded()) {
            // The lazy schema around it takes the relationship's name as its key, so
            // a blank key here keeps the fields' keys as `headMetadata.title.en`
            // rather than repeating the prefix.
            $fields->key('');
        }

        return [
            Section::make($this->heading ?? __('filament-head::filament-head.section.heading'))
                ->description(__('filament-head::filament-head.section.description'))
                ->collapsible()
                ->collapsed($this->isCollapsed)
                ->columnSpanFull()
                ->schema(fn (): array|Schema => $this->sectionContent($fields)),
        ];
    }

    /**
     * The section's fields, lazily loaded where Filament supports it: the content
     * renders once the section is opened and scrolled into view, so the defaults —
     * a drawn sharing card among them — are only resolved when someone looks.
     *
     * @return array<int, Component>|Schema
     */
    protected function sectionContent(Group $fields): array|Schema
    {
        if (! $this->isLazyLoaded()) {
            return [$fields];
        }

        // A lazily loaded schema needs a key unique within the form. The
        // relationship's name is one: a second set of fields on the same form
        // would be writing to the same relationship. Inside a repeater, Filament
        // prefixes it with the item's key.
        $schema = Schema::make()->components([$fields])->key(static::RELATIONSHIP);

        return method_exists($schema, 'lazyLoaded') // @phpstan-ignore function.alreadyNarrowedType
            ? $schema->lazyLoaded()
            : $schema->deferLoading(); // @phpstan-ignore method.deprecated
    }

    /**
     * A tab per locale, or a bare set of fields when there is only one.
     *
     * @return array<int, Component>
     */
    protected function translatableFields(): array
    {
        $locales = $this->getLocales();

        if (count($locales) === 1) {
            return $this->fieldsForLocale($locales[0]);
        }

        return [
            Tabs::make()->tabs(array_map(
                fn (string $locale): Tabs\Tab => Tabs\Tab::make(strtoupper($locale))
                    ->schema($this->fieldsForLocale($locale)),
                $locales,
            ))->columnSpanFull(),
        ];
    }

    /**
     * @return array<int, Component>
     */
    protected function fieldsForLocale(string $locale): array
    {
        $fields = [
            'title' => TextInput::make("title.{$locale}")
                ->label(__('filament-head::filament-head.fields.title'))
                ->placeholder(fn (): ?string => $this->defaultsFor($locale)->title)
                ->live(debounce: '500ms')
                ->hint(fn (?string $state): string => $this->counter($state, $this->getTitleLimit()))
                ->hintColor(fn (?string $state): ?string => $this->counterColor($state, $this->getTitleLimit())),
            'description' => Textarea::make("description.{$locale}")
                ->label(__('filament-head::filament-head.fields.description'))
                ->placeholder(fn (): ?string => $this->defaultsFor($locale)->description)
                ->rows(2)
                ->live(debounce: '500ms')
                ->hint(fn (?string $state): string => $this->counter($state, $this->getDescriptionLimit()))
                ->hintColor(fn (?string $state): ?string => $this->counterColor($state, $this->getDescriptionLimit())),
            'og_title' => TextInput::make("og_title.{$locale}")
                ->label(__('filament-head::filament-head.fields.og_title'))
                ->placeholder(fn (Get $get): ?string => $this->defaultsFor($locale)->ogTitle ?? $this->resolvedTitle($get, $locale))
                ->helperText(fn (Get $get): string => $this->reuseHint('og_title', $get("title.{$locale}"))),
            'og_description' => Textarea::make("og_description.{$locale}")
                ->label(__('filament-head::filament-head.fields.og_description'))
                ->placeholder(fn (Get $get): ?string => $this->defaultsFor($locale)->ogDescription ?? $this->resolvedDescription($get, $locale))
                ->rows(2)
                ->helperText(fn (Get $get): string => $this->reuseHint('og_description', $get("description.{$locale}"))),
            'canonical_url' => TextInput::make("canonical_url.{$locale}")
                ->label(__('filament-head::filament-head.fields.canonical_url'))
                ->placeholder(fn (): ?string => $this->defaultsFor($locale)->canonicalUrl)
                ->helperText(__('filament-head::filament-head.helpers.canonical_url'))
                ->url(),
        ];

        return $this->reject($fields);
    }

    /**
     * The fields that are stored once, whatever the locale.
     *
     * @return array<int, Component>
     */
    protected function plainFields(): array
    {
        $ogTypes = $this->enumOptions(OgType::cases(), 'og_types');
        // App and Player cards need twitter:app:* or twitter:player tags this form has
        // no fields for; without them X shows no card at all.
        $twitterCards = $this->enumOptions([TwitterCard::Summary, TwitterCard::SummaryWithLargeImage], 'twitter_cards');
        $robots = [
            'all' => __('filament-head::filament-head.robots.all'),
            'noindex, follow' => __('filament-head::filament-head.robots.noindex_follow'),
            'noindex, nofollow' => __('filament-head::filament-head.robots.noindex_nofollow'),
            'nofollow' => __('filament-head::filament-head.robots.nofollow'),
        ];

        $fields = [
            'og_image' => FileUpload::make('og_image')
                ->label(__('filament-head::filament-head.fields.og_image'))
                ->helperText(new RenderedHtml(fn (): string|Htmlable => $this->ogImageHelper()))
                ->image()
                ->disk($this->getDisk())
                ->directory($this->getDirectory())
                ->visibility('public'),
            'og_type' => Select::make('og_type')
                ->label(__('filament-head::filament-head.fields.og_type'))
                ->options($ogTypes)
                ->placeholder(fn (): string => $this->selectPlaceholder($ogTypes, $this->sharedDefaults()->ogType)),
            'twitter_card' => Select::make('twitter_card')
                ->label(__('filament-head::filament-head.fields.twitter_card'))
                ->options($twitterCards)
                ->placeholder(fn (): string => $this->selectPlaceholder($twitterCards, $this->sharedDefaults()->twitterCard)),
            'robots' => Select::make('robots')
                ->label(__('filament-head::filament-head.fields.robots'))
                ->options($robots)
                // No robots tag at all reads as "index, follow", so that is what a blank field means.
                ->placeholder(fn (): string => $this->selectPlaceholder($robots, $this->sharedDefaults()->robots ?? 'all'))
                // A blank field and "Index and follow links" read alike but are not:
                // blank defers to the record's and the site's defaults, the option overrides them.
                ->helperText(__('filament-head::filament-head.helpers.robots')),
        ];

        return $this->reject($fields);
    }

    /**
     * What the record falls back to in $locale for the fields left blank: its
     * headDefaults(), resolved in that locale so each tab shows its own language.
     * Empty on create, and for a model without HasHeadMetadata.
     */
    protected function defaultsFor(string $locale): HeadDefaults
    {
        return $this->resolvedDefaults[$locale] ??= $this->withLocale($locale, function (): HeadDefaults {
            $record = $this->getRecord();

            if (! $record instanceof Model || ! method_exists($record, 'headDefaults')) {
                return new HeadDefaults;
            }

            return HeadDefaults::wrap($record->headDefaults());
        });
    }

    /**
     * The title the page ends up with: what was typed, else the record's default.
     * laravel/head falls back to it for og:title, so a blank Open Graph title
     * shows it too.
     */
    protected function resolvedTitle(Get $get, string $locale): ?string
    {
        $typed = $get("title.{$locale}");

        return filled($typed) && is_string($typed) ? $typed : $this->defaultsFor($locale)->title;
    }

    /**
     * The meta description the page ends up with, which laravel/head reuses for
     * og:description.
     */
    protected function resolvedDescription(Get $get, string $locale): ?string
    {
        $typed = $get("description.{$locale}");

        return filled($typed) && is_string($typed) ? $typed : $this->defaultsFor($locale)->description;
    }

    /**
     * Defaults for the fields stored once, whatever the locale, resolved in the
     * site's primary locale rather than the admin's: someone browsing the panel in
     * Spanish should still see what the main site shares.
     */
    protected function sharedDefaults(): HeadDefaults
    {
        return $this->defaultsFor($this->primaryLocale());
    }

    /**
     * The locale applyHead() falls back to — filament-head.fallback_locale, else
     * app.fallback_locale — when the form offers it, else the first one it offers.
     * Not app.locale: App::setLocale() rewrites it to the admin's locale.
     */
    protected function primaryLocale(): string
    {
        $locales = $this->getLocales();
        $fallback = config('filament-head.fallback_locale') ?? config('app.fallback_locale');

        return is_string($fallback) && in_array($fallback, $locales, true)
            ? $fallback
            : $locales[0];
    }

    /**
     * The label of the option the record defaults to, marked as the default so
     * it does not read as a second copy of that option in the open list; or
     * Filament's own "Select an option" when it has none, since setting a
     * placeholder replaces that one and it has to be put back by hand.
     *
     * @param  array<string, string>  $options
     */
    protected function selectPlaceholder(array $options, ?string $default): string
    {
        if (blank($default)) {
            return __('filament-forms::components.select.placeholder');
        }

        return __('filament-head::filament-head.helpers.select_default', [
            'value' => $options[$default] ?? $default,
        ]);
    }

    /**
     * The upload's hint, under a preview of the image shared today when there
     * is one: a file input has no placeholder to show it in. The model's own
     * default wins; the plugin covers an image the application draws itself.
     */
    protected function ogImageHelper(): string|Htmlable
    {
        $hint = __('filament-head::filament-head.helpers.og_image');
        $current = $this->currentOgImage();

        if ($current === null) {
            return $hint;
        }

        // Inline styles: the host panel's Tailwind build never scans this package's classes.
        return new HtmlString(sprintf(
            '<a href="%1$s" target="_blank" rel="noopener" style="display:block;width:max-content;max-width:100%%;margin-bottom:.5rem">'
            .'<img src="%1$s" alt="%2$s" loading="lazy" style="display:block;width:16rem;max-width:100%%;aspect-ratio:1200/630;object-fit:cover;border-radius:.5rem">'
            .'</a>%3$s',
            e($current),
            e(__('filament-head::filament-head.helpers.og_image_current')),
            e($hint),
        ));
    }

    /**
     * Resolved once per request: the helper text is re-rendered on every
     * Livewire update, and the plugin's closure may draw the image.
     */
    protected function currentOgImage(): ?string
    {
        if ($this->hasResolvedOgImage) {
            return $this->resolvedOgImage;
        }

        $this->hasResolvedOgImage = true;

        $url = $this->sharedDefaults()->ogImage;

        if (blank($url) && ($record = $this->getRecord()) instanceof Model) {
            $url = FilamentHeadPlugin::get()?->getDefaultOgImage($record);
        }

        return $this->resolvedOgImage = $this->previewableUrl($url);
    }

    /**
     * The URL when it is safe to put in a link and an image: http(s), protocol
     * relative or root relative. Anything else — `javascript:`, `data:` — would
     * stay a live link however well it is escaped, so it gets no preview.
     */
    protected function previewableUrl(?string $url): ?string
    {
        if (blank($url)) {
            return null;
        }

        $url = trim($url);
        $scheme = parse_url($url, PHP_URL_SCHEME);

        if (is_string($scheme)) {
            return in_array(strtolower($scheme), static::PREVIEW_SCHEMES, true) ? $url : null;
        }

        return str_starts_with($url, '/') ? $url : null;
    }

    /**
     * Drop the fields ->without() named.
     *
     * @param  array<string, Component>  $fields
     * @return array<int, Component>
     */
    protected function reject(array $fields): array
    {
        return array_values(array_diff_key($fields, array_flip($this->hiddenFields)));
    }

    /**
     * "Leave blank to reuse the title", naming the value it would reuse when this
     * locale's sibling field holds one. The model's own default is already the
     * placeholder, so the hint only quotes what was typed beside it.
     */
    protected function reuseHint(string $field, mixed $reused): string
    {
        if (blank($reused) || ! is_string($reused)) {
            return __("filament-head::filament-head.helpers.{$field}");
        }

        return __("filament-head::filament-head.helpers.{$field}_reusing", [
            'value' => str($reused)->squish()->limit(60)->toString(),
        ]);
    }

    /**
     * A hint, never a validation rule: over-length titles still save.
     *
     * It rides on the label row rather than under the field because Textarea has no
     * affixes — only TextInput does — and a counter inside one box but above the
     * other would read as two different things.
     */
    protected function counter(?string $state, int $limit): string
    {
        $count = mb_strlen((string) $state);

        $key = $this->isOverLimit($state, $limit) ? 'counter_over' : 'counter';

        return __("filament-head::filament-head.helpers.{$key}", ['count' => $count, 'limit' => $limit]);
    }

    /**
     * Red past the limit, inherited otherwise.
     */
    protected function counterColor(?string $state, int $limit): ?string
    {
        return $this->isOverLimit($state, $limit) ? 'danger' : null;
    }

    protected function isOverLimit(?string $state, int $limit): bool
    {
        return mb_strlen((string) $state) > $limit;
    }

    /**
     * Translated labels keyed by the enum's value. The key is the case name in
     * snake case: values such as `music.song` hold a dot, which the translator
     * would read as nesting. A case laravel/head adds before this package
     * translates it falls back to its name as a headline, never the raw key.
     *
     * @param  array<int, OgType|TwitterCard>  $cases
     * @return array<string, string>
     */
    protected function enumOptions(array $cases, string $group): array
    {
        $options = [];

        foreach ($cases as $case) {
            $key = "filament-head::filament-head.{$group}.".str($case->name)->snake();

            $options[$case->value] = Lang::has($key)
                ? (string) __($key)
                : str($case->name)->headline()->toString();
        }

        return $options;
    }

    protected function getDisk(): string
    {
        return FilamentHeadPlugin::get()?->getDisk()
            ?? (string) config('filament-head.disk', 'public');
    }

    protected function getDirectory(): string
    {
        return FilamentHeadPlugin::get()?->getDirectory()
            ?? (string) config('filament-head.directory', 'head-metadata');
    }

    protected function getTitleLimit(): int
    {
        return FilamentHeadPlugin::get()?->getTitleLimit()
            ?? (int) config('filament-head.title_limit', 60);
    }

    protected function getDescriptionLimit(): int
    {
        return FilamentHeadPlugin::get()?->getDescriptionLimit()
            ?? (int) config('filament-head.description_limit', 160);
    }
}
