<?php

namespace Arzcode\FilamentHead;

use Closure;
use Filament\Contracts\Plugin;
use Filament\Facades\Filament;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;
use Illuminate\Database\Eloquent\Model;

/**
 * Carries panel-level configuration for the form component. Registering it is
 * optional: without it, HeadMetadataFields reads config/filament-head.php.
 *
 * HasHeadMetadata::applyHead() never reads the plugin — the public site has no panel.
 */
class FilamentHeadPlugin implements Plugin
{
    use EvaluatesClosures;

    public const ID = 'filament-head';

    /** @var array<int, string>|Closure|null */
    protected array|Closure|null $locales = null;

    protected string|Closure|null $disk = null;

    protected string|Closure|null $directory = null;

    protected int|Closure|null $titleLimit = null;

    protected int|Closure|null $descriptionLimit = null;

    protected ?Closure $defaultOgImage = null;

    public static function make(): static
    {
        return app(static::class);
    }

    /**
     * The plugin registered on the current panel, or null when there is none.
     */
    public static function get(): ?static
    {
        $panel = Filament::getCurrentOrDefaultPanel();

        if ($panel === null || ! $panel->hasPlugin(static::ID)) {
            return null;
        }

        /** @var static $plugin */
        $plugin = $panel->getPlugin(static::ID);

        return $plugin;
    }

    /**
     * The plugin registered on a given panel, or null when that panel has none.
     */
    public static function for(Panel $panel): ?static
    {
        if (! $panel->hasPlugin(static::ID)) {
            return null;
        }

        /** @var static $plugin */
        $plugin = $panel->getPlugin(static::ID);

        return $plugin;
    }

    public function getId(): string
    {
        return static::ID;
    }

    public function register(Panel $panel): void {}

    public function boot(Panel $panel): void {}

    /**
     * @param  array<int, string>|Closure  $locales
     */
    public function locales(array|Closure $locales): static
    {
        $this->locales = $locales;

        return $this;
    }

    public function disk(string|Closure $disk): static
    {
        $this->disk = $disk;

        return $this;
    }

    public function directory(string|Closure $directory): static
    {
        $this->directory = $directory;

        return $this;
    }

    public function titleLimit(int|Closure $limit): static
    {
        $this->titleLimit = $limit;

        return $this;
    }

    public function descriptionLimit(int|Closure $limit): static
    {
        $this->descriptionLimit = $limit;

        return $this;
    }

    /**
     * The sharing image a record goes out with when the form leaves it blank,
     * for an image the application draws itself rather than one the model can
     * name. The closure receives the record — as `$record`, `$model`, or by any
     * type it satisfies — and returns an absolute http(s) URL or null. Any other
     * scheme gets no preview.
     *
     * It only feeds the form's preview: applyHead() runs outside any panel, so
     * the public page must keep setting that image itself.
     */
    public function defaultOgImage(Closure $callback): static
    {
        $this->defaultOgImage = $callback;

        return $this;
    }

    /**
     * @return array<int, string>|null
     */
    public function getLocales(): ?array
    {
        /** @var array<int, string>|null $locales */
        $locales = $this->evaluate($this->locales);

        return $locales;
    }

    public function getDisk(): ?string
    {
        $disk = $this->evaluate($this->disk);

        return filled($disk) ? (string) $disk : null;
    }

    public function getDirectory(): ?string
    {
        $directory = $this->evaluate($this->directory);

        return filled($directory) ? (string) $directory : null;
    }

    public function getTitleLimit(): ?int
    {
        $limit = $this->evaluate($this->titleLimit);

        return $limit === null ? null : (int) $limit;
    }

    public function getDescriptionLimit(): ?int
    {
        $limit = $this->evaluate($this->descriptionLimit);

        return $limit === null ? null : (int) $limit;
    }

    /**
     * The closure may name the record `$record` or `$model`, or type it as its
     * class, any parent class or any interface it implements.
     */
    public function getDefaultOgImage(Model $record): ?string
    {
        if ($this->defaultOgImage === null) {
            return null;
        }

        $typed = [];

        foreach ([$record::class, ...class_parents($record), ...class_implements($record)] as $type) {
            $typed[$type] = $record;
        }

        $url = $this->evaluate($this->defaultOgImage, ['record' => $record, 'model' => $record], $typed);

        return filled($url) ? (string) $url : null;
    }
}
