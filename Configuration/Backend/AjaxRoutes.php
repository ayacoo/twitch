<?php

use Ayacoo\Twitch\Controller\OnlineMediaUpdateController;

return [
    'ayacoo_twitch_online_media_updater' => [
        'path' => '/ayacoo-twitch/update',
        'target' => OnlineMediaUpdateController::class . '::updateAction',
    ],
];
