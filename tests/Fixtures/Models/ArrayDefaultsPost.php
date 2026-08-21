<?php

namespace Arzcode\FilamentHead\Tests\Fixtures\Models;

use Arzcode\FilamentHead\Concerns\HasHeadMetadata;
use Illuminate\Database\Eloquent\Model;

/**
 * Returns its defaults as a plain array, the other half of headDefaults()'s union.
 */
class ArrayDefaultsPost extends Model
{
    use HasHeadMetadata;

    protected $table = 'posts';

    protected $guarded = [];

    /**
     * @return array<string, string|null>
     */
    public function headDefaults(): array
    {
        return [
            'title' => 'From an array',
            'robots' => 'nofollow',
        ];
    }
}
