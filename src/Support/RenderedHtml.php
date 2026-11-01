<?php

namespace Arzcode\FilamentHead\Support;

use Closure;
use Illuminate\Contracts\Support\Htmlable;

/**
 * HTML built when it is printed, not when it is handed over.
 *
 * Filament evaluates a field's helper text while it hydrates the form on mount,
 * long before — and whether or not — the field is rendered. Wrapping the work in
 * this keeps that evaluation free, so a lazily loaded section does the work only
 * once it is actually shown.
 */
final readonly class RenderedHtml implements Htmlable
{
    /**
     * @param  Closure(): (string|Htmlable)  $render  plain strings are escaped
     */
    public function __construct(private Closure $render) {}

    public function toHtml(): string
    {
        $html = ($this->render)();

        return $html instanceof Htmlable ? $html->toHtml() : e($html);
    }
}
