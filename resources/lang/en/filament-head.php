<?php

return [

    'section' => [
        'heading' => 'SEO & sharing',
        'description' => 'Overrides the metadata search engines and social networks use for this record.',
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
        'og_description' => 'Leave blank to reuse the meta description.',
        'og_image' => 'Shown when the page is shared. 1200×630 works everywhere.',
        'canonical_url' => 'The address this page should be indexed under.',
    ],

    'exceptions' => [
        'unknown_field' => 'Cannot hide [:fields] from the head metadata section. Only these fields are optional: :allowed.',
    ],

    'robots' => [
        'all' => 'Index and follow links (default)',
        'noindex_follow' => 'Do not index, follow links',
        'noindex_nofollow' => 'Do not index, do not follow links',
        'nofollow' => 'Index, do not follow links',
    ],

];
