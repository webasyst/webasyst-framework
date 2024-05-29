<?php
/**
 * default application settings
 */
return array(
    /**
     * count comments per page at backend
     */
    'comments_per_page'=>10,

    /**
     * count posts per page at backend
     */
    'posts_per_page'=>10,

    /**
     * list of available CSS classes for blog coloring
      */
    'colors' => array(
        'b-white',
        'b-gray',
        'b-yellow',
        'b-green',
        'b-blue',
        'b-red',
        'b-purple',
    ),

    /**
     * list of available blog icons (CSS classes)
     */
    'icons'    => array(
            'blog fas fa-file-invoice',
            'notebook fas fa-file-alt',
            'lock fas fa-lock',
            'lock-unlocked fas fa-unlock',
            'broom fas fa-broom',
            'star fas fa-star',
            'livejournal fas fa-pencil-alt',
            'contact fas fa-users',
            'lightning fas fa-bolt',
            'light-bulb fas fa-lightbulb',
            'pictures far fa-images',
            'reports fas fa-chart-pie',
            'books fas fa-book',
            'marker fas fa-map-marker-alt',
            'lens fas fa-eye',
            'alarm-clock fas fa-clock',
            'animal-monkey fas fa-cat',
            'anchor fas fa-anchor',
            'bean fas fa-beer',
            'car fas fa-car',
            'disk fas fa-save',
            'cookie fas fa-cookie',
            'burn fas fa-burn',
            'clapperboard fas fa-film',
            'bug fas fa-bug',
            'clock fas fa-clock',
            'cup fas fa-coffee',
            'home fas fa-home',
            'fruit fas fa-apple-alt',
            'luggage fas fa-briefcase',
            'guitar fas fa-guitar',
            'smiley fas fa-smile',
            'sport-soccer fas fa-futbol',
            'target fas fa-bullseye',
            'medal fas fa-medal',
            'phone fas fa-phone',
            'store fas fa-store',
),

    /**
     * caching options
     */
    'cache_time'=>1800,

    /**
     * the ability to use Smarty within post body
     */
    'can_use_smarty'=>false,
);

