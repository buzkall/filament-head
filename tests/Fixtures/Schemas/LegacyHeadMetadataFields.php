<?php

namespace Arzcode\FilamentHead\Tests\Fixtures\Schemas;

use Arzcode\FilamentHead\Schemas\HeadMetadataFields;

/**
 * The component as it behaves on a Filament version without lazily loaded schemas.
 */
class LegacyHeadMetadataFields extends HeadMetadataFields
{
    public static function supportsLazyLoading(): bool
    {
        return false;
    }
}
