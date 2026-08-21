<?php

namespace Arzcode\FilamentHead\Tests\Fixtures\Resources\Pages;

use Arzcode\FilamentHead\Tests\Fixtures\Resources\PostResource;
use Filament\Resources\Pages\EditRecord;

class EditPost extends EditRecord
{
    protected static string $resource = PostResource::class;
}
