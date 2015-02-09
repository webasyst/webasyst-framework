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
            'blog',
            'notebook',
            'lock',
            'lock-unlocked',
            'broom',
            'star',
            'livejournal',
            'contact',
            'lightning',
            'light-bulb',
            'pictures',
            'reports',
            'books',
            'marker',
            'lens',
            'alarm-clock',
            'animal-monkey',
            'anchor',
            'bean',
            'car',
            'disk',
            'cookie',
            'burn',
            'clapperboard',
            'bug',
            'clock',
            'cup',
            'home',
            'fruit',
            'luggage',
            'guitar',
            'smiley',
            'sport-soccer',
            'target',
            'medal',
            'phone',
            'store',
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

