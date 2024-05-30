<?php

use Ayacoo\Twitch\Helper\TwitchHelper;
use Ayacoo\Twitch\Rendering\TwitchRenderer;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Resource\Rendering\RendererRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;

defined('TYPO3') or die();

(function ($mediaFileExt) {
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['onlineMediaHelpers'][$mediaFileExt] = TwitchHelper::class;

    $rendererRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(RendererRegistry::class);
    $rendererRegistry->registerRendererClass(TwitchRenderer::class);

    $GLOBALS['TYPO3_CONF_VARS']['SYS']['FileInfo']['fileExtensionToMimeType'][$mediaFileExt] = 'video/' . $mediaFileExt;
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['mediafile_ext'] .= ',' . $mediaFileExt;

    $iconRegistry = GeneralUtility::makeInstance(IconRegistry::class);
    $iconRegistry->registerFileExtension($mediaFileExt, 'mimetypes-media-image-' . $mediaFileExt);
})('twitch');
