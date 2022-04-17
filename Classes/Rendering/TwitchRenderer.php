<?php

declare(strict_types=1);

namespace Ayacoo\Twitch\Rendering;

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Resource\OnlineMedia\Helpers\OnlineMediaHelperInterface;
use TYPO3\CMS\Core\Resource\OnlineMedia\Helpers\OnlineMediaHelperRegistry;
use TYPO3\CMS\Core\Resource\Rendering\FileRendererInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Twitch renderer class
 */
class TwitchRenderer implements FileRendererInterface
{
    /**
     * @var OnlineMediaHelperInterface|false
     */
    protected $onlineMediaHelper;

    /**
     * Returns the priority of the renderer
     * This way it is possible to define/overrule a renderer
     * for a specific file type/context.
     * For example create a video renderer for a certain storage/driver type.
     * Should be between 1 and 100, 100 is more important than 1
     *
     * @return int
     */
    public function getPriority()
    {
        return 1;
    }

    /**
     * Check if given File(Reference) can be rendered
     *
     * @param FileInterface $file File of FileReference to render
     * @return bool
     */
    public function canRender(FileInterface $file)
    {
        return ($file->getMimeType() === 'video/twitch' || $file->getExtension() === 'twitch') && $this->getOnlineMediaHelper($file) !== false;
    }

    public function render(FileInterface $file, $width, $height, array $options = [], $usedPathsRelativeToCurrentScript = false)
    {
        $videoId = $this->getVideoIdFromFile($file);

        $extConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('twitch');
        if ($extConf['display'] === 'iframe') {
            return $this->renderIframe($options, $videoId, (int)$width, (int)$height);
        }

        return $this->renderJavaScript($options, $videoId, (int)$width, (int)$height);
    }

    /**
     * Get online media helper
     *
     * @param FileInterface $file
     * @return false|OnlineMediaHelperInterface
     */
    protected function getOnlineMediaHelper(FileInterface $file)
    {
        if ($this->onlineMediaHelper === null) {
            $orgFile = $file;
            if ($orgFile instanceof FileReference) {
                $orgFile = $orgFile->getOriginalFile();
            }
            if ($orgFile instanceof File) {
                $this->onlineMediaHelper = GeneralUtility::makeInstance(OnlineMediaHelperRegistry::class)->getOnlineMediaHelper($orgFile);
            } else {
                $this->onlineMediaHelper = false;
            }
        }
        return $this->onlineMediaHelper;
    }

    /**
     * @param FileInterface $file
     * @return string
     */
    protected function getVideoIdFromFile(FileInterface $file)
    {
        if ($file instanceof FileReference) {
            $orgFile = $file->getOriginalFile();
        } else {
            $orgFile = $file;
        }

        return $this->getOnlineMediaHelper($file)->getOnlineMediaId($orgFile);
    }

    /**
     * @see https://dev.twitch.tv/docs/embed/video-and-clips/
     *
     * @param array $options
     * @param string $videoId
     * @param int|string $width
     * @param int|string $height
     * @return string
     */
    protected function renderIframe(array $options, string $videoId, int|string $width, int|string $height): string
    {
        $urlParams = [];
        if (!empty($options['autoplay'])) {
            $urlParams[] = 'autoplay=true';
        } else {
            $urlParams[] = 'autoplay=false';
        }
        if (!empty($options['muted'])) {
            $urlParams[] = 'muted=true';
        } else {
            $urlParams[] = 'muted=false';
        }
        if (!empty($options['time'])) {
            $urlParams[] = 'time=' . htmlspecialchars($options['time']);
        }

        return sprintf(
            '<iframe src="https://player.twitch.tv/?video=%s&parent=%s&%s" frameborder="0" allowfullscreen="true" scrolling="no" height="%d" width="%d"></iframe>',
            rawurlencode($videoId),
            rawurlencode(GeneralUtility::getIndpEnv('TYPO3_HOST_ONLY')),
            implode('&', $urlParams),
            $height,
            $width
        );
    }

    /**
     * @see https://dev.twitch.tv/docs/embed/video-and-clips/
     *
     * @param array $options
     * @param string $videoId
     * @param int|string $width
     * @param int|string $height
     * @return string
     */
    protected function renderJavaScript(array $options, string $videoId, int|string $width, int|string $height): string
    {
        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $pageRenderer->addJsFile('https://player.twitch.tv/js/embed/v1.js');

        $autoplay = 'false';
        if (!empty($options['autoplay'])) {
            $autoplay = 'true';
        }
        $muted = 'false';
        if (!empty($options['muted'])) {
            $muted = 'true';
        }
        $time = '0h0m0s';
        if (!empty($options['time'])) {
            $time = htmlspecialchars($options['time']);
        }

        $uniqueId = uniqid();
        return '<div id="twitch-embed' . $uniqueId . '"></div>
            <script type="text/javascript">
              var options = {
                width: ' . $width . ',
                height: ' . $height . ',
                video: "' . rawurlencode($videoId) . '",
                autoplay: ' . $autoplay . ',
                muted: ' . $muted . ',
                time: "' . $time . '"
              };
              new Twitch.Player("twitch-embed' . $uniqueId . '", options);
            </script>';
    }
}
