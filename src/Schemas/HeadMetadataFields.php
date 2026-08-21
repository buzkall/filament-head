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
use Illuminate\Contracts\Support\Htmlable;
use Laravel\Head\Enums\OgType;
use Laravel\Head\Enums\TwitterCard;
use LogicException;

/**
 * The whole editing UI, as a single section that drops into any resource form.
 *
 * The fields are wrapped in a Group bound to the `headMetadata` relationship, so
 * Create and Edit pages persist the morphOne without any page-class changes.
 */
class HeadMetadataFields extends Section
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

    /** @var array<int, string>|null */
    protected ?array $localeOverride = null;

    /** @var array<int, string> */
    protected array $hiddenFields = [];

    public static function make(string|array|Htmlable|Closure|null $heading = null): static
    {
        /** @var static $static */
        $static = app(static::class, [
            'heading' => $heading ?? __('filament-head::filament-head.section.heading'),
        ]);
        $static->configure();

        return $static;
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

        $this->description(__('filament-head::filament-head.section.description'))
            ->collapsible()
            ->columnSpanFull()
            // Built lazily, so ->locales() and ->without() have already run.
            ->schema(fn (): array => $this->buildSchema());
    }

    /**
     * @return array<int, Component>
     */
    protected function buildSchema(): array
    {
        return [
            Group::make([
                ...$this->translatableFields(),
                ...$this->plainFields(),
            ])->relationship('headMetadata'),
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
                ->helperText(__('filament-head::filament-head.helpers.og_title')),
            'og_description' => Textarea::make("og_description.{$locale}")
                ->label(__('filament-head::filament-head.fields.og_description'))
                ->rows(2)
                ->helperText(__('filament-head::filament-head.helpers.og_description')),
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
            'canonical_url' => TextInput::make('canonical_url')
                ->label(__('filament-head::filament-head.fields.canonical_url'))
                ->helperText(__('filament-head::filament-head.helpers.canonical_url'))
                ->url(),
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
