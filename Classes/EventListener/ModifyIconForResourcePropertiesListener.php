<?php

declare(strict_types=1);

namespace Ayacoo\Twitch\EventListener;

use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Imaging\Event\ModifyIconForResourcePropertiesEvent;
use TYPO3\CMS\Core\Resource\File;

/**
 * Adjusts the icon for resources with mime type "video/twitch".
 */
final class ModifyIconForResourcePropertiesListener
{
    #[AsEventListener(identifier: 'twitch/modify-icon-for-resource')]
    public function __invoke(ModifyIconForResourcePropertiesEvent $event): void
    {
        $resource = $event->getResource();

        if (!$resource instanceof File) {
            return;
        }

        if ($resource->getMimeType() === 'video/twitch') {
            $event->setIconIdentifier('mimetypes-media-image-twitch');
        }
    }
}
