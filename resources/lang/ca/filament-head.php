<?php

return [

    'section' => [
        'heading' => 'SEO i compartir',
        'description' => 'Substitueix les metadades que fan servir els cercadors i les xarxes socials per a aquest registre. En gris, el que es fa servir ara: deixa el camp en blanc per mantenir-ho.',
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
        'og_image_current' => 'La imatge que es comparteix ara',
        'select_default' => ':value (per defecte)',
        'canonical_url' => 'L\'adreça sota la qual s\'ha d\'indexar aquesta pàgina.',
        'robots' => 'Deixa-ho en blanc per mantenir el valor per defecte. Triar una opció sempre el substitueix, fins i tot «Indexar i seguir enllaços».',
    ],

    'exceptions' => [
        'unknown_field' => 'No es poden amagar [:fields] de la secció de metadades. Només aquests camps són opcionals: :allowed.',
    ],

    'og_types' => [
        'website' => 'Lloc web',
        'article' => 'Article',
        'book' => 'Llibre',
        'profile' => 'Perfil',
        'music_song' => 'Cançó',
        'music_album' => 'Àlbum',
        'music_playlist' => 'Llista de reproducció',
        'music_radio_station' => 'Emissora de ràdio',
        'video_movie' => 'Pel·lícula',
        'video_episode' => 'Episodi',
        'video_tv_show' => 'Sèrie de televisió',
        'video_other' => 'Altre vídeo',
    ],

    'twitter_cards' => [
        'summary' => 'Resum amb imatge petita',
        'summary_with_large_image' => 'Resum amb imatge gran',
        'app' => 'Aplicació',
        'player' => 'Reproductor',
    ],

    'robots' => [
        'all' => 'Indexar i seguir enllaços',
        'noindex_follow' => 'No indexar, seguir enllaços',
        'noindex_nofollow' => 'No indexar, no seguir enllaços',
        'nofollow' => 'Indexar, no seguir enllaços',
    ],

];
