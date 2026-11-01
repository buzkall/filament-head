<?php

return [

    'section' => [
        'heading' => 'SEO y compartir',
        'description' => 'Sustituye los metadatos que usan los buscadores y las redes sociales para este registro. En gris, lo que se usa ahora: deja el campo en blanco para mantenerlo.',
    ],

    'fields' => [
        'title' => 'Título',
        'description' => 'Meta descripción',
        'og_title' => 'Título de Open Graph',
        'og_description' => 'Descripción de Open Graph',
        'og_image' => 'Imagen para compartir',
        'og_type' => 'Tipo de contenido',
        'twitter_card' => 'Tarjeta de Twitter',
        'canonical_url' => 'URL canónica',
        'robots' => 'Indexación en buscadores',
    ],

    'helpers' => [
        'counter' => ':count/:limit caracteres',
        'counter_over' => ':count/:limit caracteres — más largo de lo recomendado',
        'og_title' => 'Déjalo en blanco para reutilizar el título.',
        'og_title_reusing' => 'Déjalo en blanco para reutilizar el título: «:value»',
        'og_description' => 'Déjalo en blanco para reutilizar la meta descripción.',
        'og_description_reusing' => 'Déjalo en blanco para reutilizar la meta descripción: «:value»',
        'og_image' => 'Se muestra al compartir la página. 1200×630 funciona en todas partes.',
        'og_image_current' => 'La imagen que se comparte ahora',
        'select_default' => ':value (por defecto)',
        'canonical_url' => 'La dirección bajo la que debe indexarse esta página.',
        'robots' => 'Déjalo en blanco para mantener el valor por defecto. Elegir una opción siempre lo sustituye, incluso «Indexar y seguir enlaces».',
    ],

    'exceptions' => [
        'unknown_field' => 'No se pueden ocultar [:fields] de la sección de metadatos. Solo estos campos son opcionales: :allowed.',
    ],

    'og_types' => [
        'website' => 'Sitio web',
        'article' => 'Artículo',
        'book' => 'Libro',
        'profile' => 'Perfil',
        'music_song' => 'Canción',
        'music_album' => 'Álbum',
        'music_playlist' => 'Lista de reproducción',
        'music_radio_station' => 'Emisora de radio',
        'video_movie' => 'Película',
        'video_episode' => 'Episodio',
        'video_tv_show' => 'Serie de televisión',
        'video_other' => 'Otro vídeo',
    ],

    'twitter_cards' => [
        'summary' => 'Resumen con imagen pequeña',
        'summary_with_large_image' => 'Resumen con imagen grande',
    ],

    'robots' => [
        'all' => 'Indexar y seguir enlaces',
        'noindex_follow' => 'No indexar, seguir enlaces',
        'noindex_nofollow' => 'No indexar, no seguir enlaces',
        'nofollow' => 'Indexar, no seguir enlaces',
    ],

];
