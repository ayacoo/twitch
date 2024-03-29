<?php

declare(strict_types=1);

namespace Ayacoo\Twitch\Rendering;

use Ayacoo\Twitch\Event\ModifyTwitchOutputEvent;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Resource\OnlineMedia\Helpers\OnlineMediaHelperInterface;
use TYPO3\CMS\Core\Resource\OnlineMedia\Helpers\OnlineMediaHelperRegistry;
use TYPO3\CMS\Core\Resource\Rendering\FileRendererInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException;

/**
 * Twitch renderer class
 */
class TwitchRenderer implements FileRendererInterface
{
    /**
     * @var OnlineMediaHelperInterface|false
     */
    protected $onlineMediaHelper;

    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly ConfigurationManager $configurationManager
    ) {
    }

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
        return ($file->getMimeType() === 'video/twitch' || $file->getExtension() === 'twitch') &&
            $this->getOnlineMediaHelper($file) !== false;
    }

    public function render(FileInterface $file, $width, $height, array $options = [])
    {
        $videoId = $this->getVideoIdFromFile($file);

        $extConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('twitch');
        if ($extConf['display'] === 'iframe') {
            $output = $this->renderIframe($options, $videoId, (int)$width, (int)$height);
            if ($this->getPrivacySetting()) {
                $output = str_replace('src', 'data-name="script-twitch" data-src', $output);
            }
        } else {
            $output = $this->renderJavaScript($options, $videoId, (int)$width, (int)$height);
            if ($this->getPrivacySetting()) {
                $output = str_replace('text/javascript', 'text/plain', $output);
            }
        }

        $modifyTwitchOutputEvent = $this->eventDispatcher->dispatch(
            new ModifyTwitchOutputEvent($output)
        );
        return $modifyTwitchOutputEvent->getOutput();
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
                $this->onlineMediaHelper = GeneralUtility::makeInstance(OnlineMediaHelperRegistry::class)
                    ->getOnlineMediaHelper($orgFile);
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

        $iframeBase = '<iframe src="https://player.twitch.tv/?video=%s&parent=%s&%s" frameborder="0" ';
        $iframeBase .= 'allowfullscreen="true" scrolling="no" height="%d" width="%d"></iframe>';

        return sprintf(
            $iframeBase,
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

    /**
     * @return bool
     */
    protected function getPrivacySetting(): bool
    {
        try {
            $privacy = false;
            $extbaseFrameworkConfiguration = $this->configurationManager->getConfiguration(
                ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT
            );
            $extSettings = $extbaseFrameworkConfiguration['plugin.']['tx_twitch.']['settings.'] ?? null;
            if (is_array($extSettings)) {
                $privacy = (bool)$extSettings['privacy'] ?? false;
            }
            return $privacy;
        } catch (InvalidConfigurationTypeException $e) {
            return false;
        }
    }
}
