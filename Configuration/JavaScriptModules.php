<?php

return [
    'dependencies' => [
        'core',
        'backend'
    ],
    'tags' => [
        'twitch.updater',
    ],
    'imports' => [
        '@twitch/' => 'EXT:twitch/Resources/Public/JavaScript/',
    ],
];
