<?php

namespace Arzcode\FilamentHead\Tests\Fixtures\Models;

use Arzcode\FilamentHead\Concerns\HasHeadMetadata;
use Arzcode\FilamentHead\Data\HeadDefaults;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $title
 * @property string|null $body
 */
class Post extends Model
{
    use HasHeadMetadata;

    protected $table = 'posts';

    protected $guarded = [];

    public function headDefaults(): HeadDefaults
    {
        return new HeadDefaults(
            title: $this->title,
            description: str($this->body ?? '')->limit(160)->toString(),
        );
    }
}
