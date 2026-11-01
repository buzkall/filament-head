<?php

namespace Arzcode\FilamentHead\Tests\Fixtures\Models;

use Arzcode\FilamentHead\Concerns\HasHeadMetadata;
use Arzcode\FilamentHead\Data\HeadDefaults;
use Closure;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $title
 * @property string|null $body
 */
class Post extends Model
{
    use HasHeadMetadata;

    /**
     * Lets a test add defaults. It runs inside headDefaults(), so it sees the
     * locale the defaults are resolved in.
     *
     * @var (Closure(): array<string, string|null>)|null
     */
    public static ?Closure $extraDefaults = null;

    protected $table = 'posts';

    protected $guarded = [];

    public function headDefaults(): HeadDefaults
    {
        return HeadDefaults::fromArray([
            'title' => $this->title,
            'description' => str($this->body ?? '')->limit(160)->toString(),
            ...(static::$extraDefaults !== null ? (static::$extraDefaults)() : []),
        ]);
    }
}
