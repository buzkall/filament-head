<?php

namespace Arzcode\FilamentHead\Tests\Fixtures\Resources\Pages;

use Arzcode\FilamentHead\Tests\Fixtures\Resources\PostResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePost extends CreateRecord
{
    protected static string $resource = PostResource::class;
}
