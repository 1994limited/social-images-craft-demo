<?php

/*
 * Social Images' file settings. Everything here can also be set per
 * environment. See the plugin's docs/configuration.md.
 */

return [
    // The book pages use a code template: templates/social-images/book.twig.
    'views' => [
        'book_jacket' => [
            'title' => 'Book jacket (code)',
            'template' => 'social-images/book',
            'layouts' => ['og' => [1200, 630]],
        ],
    ],
];
