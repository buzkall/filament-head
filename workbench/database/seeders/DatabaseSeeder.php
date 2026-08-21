<?php

namespace Workbench\Database\Seeders;

use Illuminate\Database\Seeder;
use Workbench\App\Models\Post;
use Workbench\App\Models\User;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->firstOrCreate(
            ['email' => 'admin@example.com'],
            ['name' => 'Admin', 'password' => 'password'],
        );

        $post = Post::query()->firstOrCreate(
            ['title' => 'How we cut our build times in half'],
            ['body' => 'A walk through the profiling that found the bottleneck, the two changes that mattered, and the ones that did not.'],
        );

        $post->headMetadata()->updateOrCreate([], [
            'title' => [
                'en' => 'How we cut our build times in half',
                'es' => 'Cómo redujimos a la mitad el tiempo de compilación',
            ],
            'description' => [
                'en' => 'The profiling that found the bottleneck, the two changes that mattered, and the ones that did not.',
                'es' => 'El análisis que encontró el cuello de botella y los dos cambios que marcaron la diferencia.',
            ],
            'og_type' => 'article',
            'twitter_card' => 'summary_large_image',
            'canonical_url' => 'https://example.com/blog/faster-builds',
            'robots' => 'all',
        ]);
    }
}
