<?php

namespace Workbench\App\Models;

use Arzcode\FilamentHead\Concerns\HasHeadMetadata;
use Arzcode\FilamentHead\Data\HeadDefaults;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasHeadMetadata;

    protected $guarded = [];

    public function headDefaults(): HeadDefaults
    {
        return new HeadDefaults(
            title: $this->title,
            description: str($this->body ?? '')->limit(160)->toString(),
        );
    }
}
