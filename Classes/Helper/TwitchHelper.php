<?php

declare(strict_types=1);

namespace Ayacoo\Twitch\Helper;

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\OnlineMedia\Helpers\AbstractOEmbedHelper;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Twitch helper class
 */
class TwitchHelper extends AbstractOEmbedHelper
{
    /**
     * Get OEmbed data
     *
     * @param string $mediaId
     * @return array|null
     */
    protected function getOEmbedData($mediaId)
    {
        $extConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('twitch');
        $token = $extConf['token'] ?? '';
        $clientId = $extConf['clientId'] ?? '';

        $oEmbedUrl = $this->getOEmbedUrl($mediaId);
        $requestFactory = GeneralUtility::makeInstanceForDi(RequestFactory::class);
        $additionalOptions = [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Client-Id' => $clientId,
            ],
        ];
        $response = $requestFactory->request($oEmbedUrl, 'GET', $additionalOptions);
        if ($response->getStatusCode() === 200) {
            $oEmbed = json_decode($response->getBody()->getContents(), true);
            if ($oEmbed['data'][0]) {
                return array_shift($oEmbed['data']);
            }
        }
        return [];
    }

    protected function getOEmbedUrl($mediaId, $format = 'json')
    {
        return sprintf(
            'https://api.twitch.tv/helix/videos?id=%s',
            rawurlencode($mediaId)
        );
    }

    public function transformUrlToFile($url, Folder $targetFolder)
    {
        $videoId = null;
        // - https://www.twitch.tv/videos/<code>
        if (preg_match('%(?:.*)twitch\.tv\/videos\/([0-9]*)%i', $url, $match)) {
            $videoId = $match[1];
        }
        if (empty($videoId)) {
            return null;
        }

        return $this->transformMediaIdToFile($videoId, $targetFolder, $this->extension);
    }

    public function getPublicUrl(File $file, $relativeToCurrentScript = false)
    {
        // @deprecated $relativeToCurrentScript since v11, will be removed in TYPO3 v12.0
        $videoId = $this->getOnlineMediaId($file);

        return sprintf('https://twitch.tv/videos/%s', rawurlencode($videoId));
    }

    /**
     * Get meta data for OnlineMedia item
     * Using the meta data from oEmbed
     *
     * @param File $file
     * @return array with metadata
     */
    public function getMetaData(File $file)
    {
        $extConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('twitch');

        $metaData = [];

        $oEmbed = $this->getOEmbedData($this->getOnlineMediaId($file));
        if ($oEmbed) {
            $metaData['width'] = $extConf['width'] ?? 800;
            $metaData['height'] = $extConf['height'] ?? 450;
            if (empty($file->getProperty('title'))) {
                $metaData['title'] = $oEmbed['title'] ?? '';
            }
            $thumbnailUrl = $oEmbed['thumbnail_url'] ?? '';
            $thumbnailUrl = str_replace('%{width}', (string)$metaData['width'], $thumbnailUrl);
            $thumbnailUrl = str_replace('%{height}', (string)$metaData['height'], $thumbnailUrl);

            $metaData['twitch_thumbnail'] = $thumbnailUrl;
        }

        return $metaData;
    }

    public function getPreviewImage(File $file)
    {
        $properties = $file->getProperties();
        $previewImageUrl = $properties['twitch_thumbnail'] ?? '';

        $videoId = $this->getOnlineMediaId($file);
        $temporaryFileName = $this->getTempFolderPath() . 'twitch' . md5($videoId) . '.png';

        if (!empty($previewImageUrl)) {
            $previewImage = GeneralUtility::getUrl($previewImageUrl);
            file_put_contents($temporaryFileName, $previewImage);
            GeneralUtility::fixPermissions($temporaryFileName);
            return $temporaryFileName;
        }

        return '';
    }
}
