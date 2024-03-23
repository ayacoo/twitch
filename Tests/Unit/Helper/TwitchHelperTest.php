<?php

declare(strict_types=1);

namespace Ayacoo\Twitch\Tests\Unit\Helper;

use Ayacoo\Twitch\Helper\TwitchHelper;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\OnlineMedia\Helpers\AbstractOEmbedHelper;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class TwitchHelperTest extends UnitTestCase
{
    private TwitchHelper $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new TwitchHelper('twitch');
    }

    /**
     * @test
     */
    public function isAbstractOEmbedHelper(): void
    {
        self::assertInstanceOf(AbstractOEmbedHelper::class, $this->subject);
    }

    /**
     * @test
     */
    public function getMetaDataWithOEmbedData()
    {
        $fileMock = $this->getMockBuilder(File::class)
            ->disableOriginalConstructor()
            ->getMock();

        $extensionConfigurationMock = $this->createMock(ExtensionConfiguration::class);
        GeneralUtility::addInstance(ExtensionConfiguration::class, $extensionConfigurationMock);
        $expectedConfiguration = ['width' => 150, 'height' => 150];
        $extensionConfigurationMock->method('get')->with('twitch')->willReturn($expectedConfiguration);

        $expectedMetaData = [
            'width' => 150,
            'height' => 150,
            'title' => 'Sample Title',
            'twitch_thumbnail' => 'https://twitch.com/thumbnail.jpg'
        ];

        // Mocking the getOnlineMediaId() and getOEmbedData() methods
        $twitchHelper = $this->getMockBuilder(TwitchHelper::class)
            ->onlyMethods(['getOnlineMediaId', 'getOEmbedData'])
            ->disableOriginalConstructor()
            ->getMock();

        $twitchHelper->method('getOnlineMediaId')->willReturn('123456');
        $twitchHelper->method('getOEmbedData')
            ->with('123456')
            ->willReturn([
                'title' => 'Sample Title',
                'thumbnail_url' => 'https://twitch.com/thumbnail.jpg'
            ]);


        $metaData = $twitchHelper->getMetaData($fileMock);

        self::assertEquals($expectedMetaData, $metaData);
    }

    /**
     * @test
     */
    public function getMetaDataWithoutOEmbedData()
    {
        $fileMock = $this->getMockBuilder(File::class)
            ->disableOriginalConstructor()
            ->getMock();

        $extensionConfigurationMock = $this->createMock(ExtensionConfiguration::class);
        GeneralUtility::addInstance(ExtensionConfiguration::class, $extensionConfigurationMock);
        $expectedConfiguration = ['width' => 150, 'height' => 150];
        $extensionConfigurationMock->method('get')->with('twitch')->willReturn($expectedConfiguration);

        // Mocking the getOnlineMediaId() and getOEmbedData() methods
        $twitchHelper = $this->getMockBuilder(TwitchHelper::class)
            ->onlyMethods(['getOnlineMediaId', 'getOEmbedData'])
            ->disableOriginalConstructor()
            ->getMock();

        $twitchHelper->method('getOnlineMediaId')->willReturn('123456');
        $twitchHelper->method('getOEmbedData')
            ->with('123456')
            ->willReturn([]);

        $metaData = $twitchHelper->getMetaData($fileMock);

        self::assertEmpty($metaData);
    }

    /**
     * @test
     */
    public function getPublicUrlReturnsPublicUrl()
    {
        $videoId = '123456';

        $fileMock = $this->getMockBuilder(File::class)
            ->disableOriginalConstructor()
            ->getMock();

        $twitchHelperMock = $this->getMockBuilder(TwitchHelper::class)
            ->onlyMethods(['getOnlineMediaId'])
            ->disableOriginalConstructor()
            ->getMock();
        $twitchHelperMock->method('getOnlineMediaId')->with($fileMock)->willReturn($videoId);

        $result = $twitchHelperMock->getPublicUrl($fileMock);
        $expectedUrl = 'https://twitch.tv/videos/123456';
        self::assertEquals($expectedUrl, $result);
    }

    /**
     * @test
     */
    public function getOEmbedUrlReturnsUrl()
    {
        $mediaId = '123456';
        $expectedUrl = 'https://api.twitch.tv/helix/videos?id=123456';

        $params = [$mediaId];
        $methodName = 'getOEmbedUrl';
        $result = $this->buildReflectionForProtectedFunction($methodName, $params);

        self::assertEquals($expectedUrl, $result);
    }

    /**
     * @test
     * @dataProvider getVideoIdDataProvider
     */
    public function getVideoIdWithValidUrlReturnsAudioIdOrNull(string $url, mixed $expectedVideoId)
    {
        $params = [$url];
        $methodName = 'getVideoId';
        $actualAudioId = $this->buildReflectionForProtectedFunction($methodName, $params);

        self::assertSame($expectedVideoId, $actualAudioId);
    }

    public static function getVideoIdDataProvider(): array
    {
        return [
            ['https://www.twitch.tv/videos/123456', '123456'],
            ['https://www.twitch.tv/', null],
            ['https://google.com', null],
        ];
    }

    private function buildReflectionForProtectedFunction(string $methodName, array $params)
    {
        $reflectionCalendar = new \ReflectionClass($this->subject);
        $method = $reflectionCalendar->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($this->subject, $params);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Clean up the GeneralUtility instance pool to remove the mock
        GeneralUtility::purgeInstances();
    }
}
