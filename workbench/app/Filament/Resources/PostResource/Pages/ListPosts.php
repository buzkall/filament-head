<?php

namespace Workbench\App\Filament\Resources\PostResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Workbench\App\Filament\Resources\PostResource;

class ListPosts extends ListRecords
{
    protected static string $resource = PostResource::class;
}
