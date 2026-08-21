<?php

namespace Arzcode\FilamentHead\Tests\Fixtures\Resources\Pages;

use Arzcode\FilamentHead\Tests\Fixtures\Resources\PostResource;
use Filament\Resources\Pages\ListRecords;

class ListPosts extends ListRecords
{
    protected static string $resource = PostResource::class;
}
