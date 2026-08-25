<?php

namespace Arzcode\FilamentHead\Schemas;

use Arzcode\FilamentHead\FilamentHeadPlugin;
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
use Illuminate\Contracts\Support\Htmlable;
use Laravel\Head\Enums\OgType;
use Laravel\Head\Enums\TwitterCard;
use LogicException;

/**
 * The whole editing UI, as one component that drops into any resource form.
 *
 * By default it renders as a collapsible Section with its own heading. Inside a tab
 * — or any container that already labels and frames its contents — call
 * ->withoutSection() to render the bare fields instead.
 *
 * The fields are wrapped in a Group bound to the `headMetadata` relationship, so
 * Create and Edit pages persist the morphOne without any page-class changes.
 */
class HeadMetadataFields extends Group
{
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
        ])->relationship('headMetadata');

        if (! $this->sectioned) {
            return [$fields];
        }

        return [
            Section::make($this->heading ?? __('filament-head::filament-head.section.heading'))
                ->description(__('filament-head::filament-head.section.description'))
                ->collapsible()
                ->columnSpanFull()
                ->schema([$fields]),
        ];
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
                ->live(onBlur: true)
                ->helperText(fn (?string $state): string => $this->counter($state, $this->getTitleLimit())),
            'description' => Textarea::make("description.{$locale}")
                ->label(__('filament-head::filament-head.fields.description'))
                ->rows(2)
                ->live(onBlur: true)
                ->helperText(fn (?string $state): string => $this->counter($state, $this->getDescriptionLimit())),
            'og_title' => TextInput::make("og_title.{$locale}")
                ->label(__('filament-head::filament-head.fields.og_title'))
                ->helperText(fn (Get $get): string => $this->reuseHint('og_title', $get("title.{$locale}"))),
            'og_description' => Textarea::make("og_description.{$locale}")
                ->label(__('filament-head::filament-head.fields.og_description'))
                ->rows(2)
                ->helperText(fn (Get $get): string => $this->reuseHint('og_description', $get("description.{$locale}"))),
            'canonical_url' => TextInput::make("canonical_url.{$locale}")
                ->label(__('filament-head::filament-head.fields.canonical_url'))
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
        $fields = [
            'og_image' => FileUpload::make('og_image')
                ->label(__('filament-head::filament-head.fields.og_image'))
                ->helperText(__('filament-head::filament-head.helpers.og_image'))
                ->image()
                ->disk($this->getDisk())
                ->directory($this->getDirectory())
                ->visibility('public'),
            'og_type' => Select::make('og_type')
                ->label(__('filament-head::filament-head.fields.og_type'))
                ->options($this->enumOptions(OgType::cases())),
            'twitter_card' => Select::make('twitter_card')
                ->label(__('filament-head::filament-head.fields.twitter_card'))
                ->options($this->enumOptions(TwitterCard::cases())),
            'robots' => Select::make('robots')
                ->label(__('filament-head::filament-head.fields.robots'))
                ->options([
                    'all' => __('filament-head::filament-head.robots.all'),
                    'noindex, follow' => __('filament-head::filament-head.robots.noindex_follow'),
                    'noindex, nofollow' => __('filament-head::filament-head.robots.noindex_nofollow'),
                    'nofollow' => __('filament-head::filament-head.robots.nofollow'),
                ]),
        ];

        return $this->reject($fields);
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
     * locale's sibling field holds one. Only the sibling is quoted: the rest of the
     * fallback chain — the model's headDefaults(), the app's Head::defaults() — is
     * not in the form, so promising a value from it would be a guess.
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
     */
    protected function counter(?string $state, int $limit): string
    {
        $count = mb_strlen((string) $state);

        $key = $count > $limit ? 'counter_over' : 'counter';

        return __("filament-head::filament-head.helpers.{$key}", ['count' => $count, 'limit' => $limit]);
    }

    /**
     * @param  array<int, OgType|TwitterCard>  $cases
     * @return array<string, string>
     */
    protected function enumOptions(array $cases): array
    {
        $options = [];

        foreach ($cases as $case) {
            $options[$case->value] = str($case->name)->headline()->toString();
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
