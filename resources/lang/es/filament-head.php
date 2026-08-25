<?php

return [

    'section' => [
        'heading' => 'SEO y compartir',
        'description' => 'Sustituye los metadatos que usan los buscadores y las redes sociales para este registro.',
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
        'og_description' => 'Déjalo en blanco para reutilizar la meta descripción.',
        'og_image' => 'Se muestra al compartir la página. 1200×630 funciona en todas partes.',
        'canonical_url' => 'La dirección bajo la que debe indexarse esta página.',
    ],

    'exceptions' => [
        'unknown_field' => 'No se pueden ocultar [:fields] de la sección de metadatos. Solo estos campos son opcionales: :allowed.',
    ],

    'robots' => [
        'all' => 'Indexar y seguir enlaces (por defecto)',
        'noindex_follow' => 'No indexar, seguir enlaces',
        'noindex_nofollow' => 'No indexar, no seguir enlaces',
        'nofollow' => 'Indexar, no seguir enlaces',
    ],

];
