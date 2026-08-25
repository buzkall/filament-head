<?php

return [

    'section' => [
        'heading' => 'SEO i compartir',
        'description' => 'Substitueix les metadades que fan servir els cercadors i les xarxes socials per a aquest registre.',
    ],

    'fields' => [
        'title' => 'Títol',
        'description' => 'Meta descripció',
        'og_title' => 'Títol d\'Open Graph',
        'og_description' => 'Descripció d\'Open Graph',
        'og_image' => 'Imatge per compartir',
        'og_type' => 'Tipus de contingut',
        'twitter_card' => 'Targeta de Twitter',
        'canonical_url' => 'URL canònica',
        'robots' => 'Indexació als cercadors',
    ],

    'helpers' => [
        'counter' => ':count/:limit caràcters',
        'counter_over' => ':count/:limit caràcters — més llarg del recomanat',
        'og_title' => 'Deixa-ho en blanc per reutilitzar el títol.',
        'og_title_reusing' => 'Deixa-ho en blanc per reutilitzar el títol: «:value»',
        'og_description' => 'Deixa-ho en blanc per reutilitzar la meta descripció.',
        'og_description_reusing' => 'Deixa-ho en blanc per reutilitzar la meta descripció: «:value»',
        'og_image' => 'Es mostra en compartir la pàgina. 1200×630 funciona a tot arreu.',
        'canonical_url' => 'L\'adreça sota la qual s\'ha d\'indexar aquesta pàgina.',
    ],

    'exceptions' => [
        'unknown_field' => 'No es poden amagar [:fields] de la secció de metadades. Només aquests camps són opcionals: :allowed.',
    ],

    'robots' => [
        'all' => 'Indexar i seguir enllaços (per defecte)',
        'noindex_follow' => 'No indexar, seguir enllaços',
        'noindex_nofollow' => 'No indexar, no seguir enllaços',
        'nofollow' => 'Indexar, no seguir enllaços',
    ],

];
