<?php

return [

    'section' => [
        'heading' => 'SEO & sharing',
        'description' => 'Overrides the metadata search engines and social networks use for this record. Greyed out is what is used today: leave a field blank to keep it.',
    ],

    'fields' => [
        'title' => 'Title',
        'description' => 'Meta description',
        'og_title' => 'Open Graph title',
        'og_description' => 'Open Graph description',
        'og_image' => 'Sharing image',
        'og_type' => 'Content type',
        'twitter_card' => 'Twitter card',
        'canonical_url' => 'Canonical URL',
        'robots' => 'Search engine indexing',
    ],

    'helpers' => [
        'counter' => ':count/:limit characters',
        'counter_over' => ':count/:limit characters — longer than recommended',
        'og_title' => 'Leave blank to reuse the title.',
        'og_title_reusing' => 'Leave blank to reuse the title: “:value”',
        'og_description' => 'Leave blank to reuse the meta description.',
        'og_description_reusing' => 'Leave blank to reuse the meta description: “:value”',
        'og_image' => 'Shown when the page is shared. 1200×630 works everywhere.',
        'og_image_current' => 'The image shared today',
        'select_default' => ':value (default)',
        'canonical_url' => 'The address this page should be indexed under.',
        'robots' => 'Leave blank to keep the default. Choosing an option always overrides it, even “Index and follow links”.',
    ],

    'exceptions' => [
        'unknown_field' => 'Cannot hide [:fields] from the head metadata section. Only these fields are optional: :allowed.',
    ],

    'og_types' => [
        'website' => 'Website',
        'article' => 'Article',
        'book' => 'Book',
        'profile' => 'Profile',
        'music_song' => 'Song',
        'music_album' => 'Album',
        'music_playlist' => 'Playlist',
        'music_radio_station' => 'Radio station',
        'video_movie' => 'Movie',
        'video_episode' => 'Episode',
        'video_tv_show' => 'TV show',
        'video_other' => 'Other video',
    ],

    'twitter_cards' => [
        'summary' => 'Summary with a small image',
        'summary_with_large_image' => 'Summary with a large image',
        'app' => 'App',
        'player' => 'Player',
    ],

    'robots' => [
        'all' => 'Index and follow links',
        'noindex_follow' => 'Do not index, follow links',
        'noindex_nofollow' => 'Do not index, do not follow links',
        'nofollow' => 'Index, do not follow links',
    ],

];
