<?php

return [

    // Disk the og:image uploads are stored on.
    'disk' => env('FILAMENT_HEAD_DISK', 'public'),

    // Directory within that disk.
    'directory' => 'head-metadata',

    // Locales offered in the form. Null renders a single, untabbed set of fields
    // for the application's active locale.
    'locales' => null,

    // Locale used when the active one has no value. Null falls back to app.fallback_locale.
    'fallback_locale' => null,

    // Recommended lengths, shown as counters under the fields. Hints, never validation.
    'title_limit' => 60,

    'description_limit' => 160,

];
