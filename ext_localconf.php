<?php

defined('TYPO3_MODE') || die();

(function ($mediaFileExt) {
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['onlineMediaHelpers'][$mediaFileExt] = \Ayacoo\Twitch\Helper\TwitchHelper::class;

    $rendererRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Resource\Rendering\RendererRegistry::class);
    $rendererRegistry->registerRendererClass(\Ayacoo\Twitch\Rendering\TwitchRenderer::class);

    $GLOBALS['TYPO3_CONF_VARS']['SYS']['FileInfo']['fileExtensionToMimeType'][$mediaFileExt] = 'video/' . $mediaFileExt;
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['mediafile_ext'] .= ',' . $mediaFileExt;

    $iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class);
    $iconRegistry->registerIcon(
        'mimetypes-media-image-' . $mediaFileExt,
        \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
        ['source' => 'EXT:twitch/Resources/Public/Icons/' . $mediaFileExt . '.svg']
    );
    $iconRegistry->registerFileExtension($mediaFileExt, 'mimetypes-media-image-' . $mediaFileExt);

})('twitch');
