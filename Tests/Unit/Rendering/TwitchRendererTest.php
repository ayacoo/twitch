<?php

declare(strict_types=1);

namespace Ayacoo\Tiktok\Tests\Unit\Rendering;

use Ayacoo\Twitch\Event\ModifyTwitchOutputEvent;
use Ayacoo\Twitch\Helper\TwitchHelper;
use Ayacoo\Twitch\Rendering\TwitchRenderer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Rendering\FileRendererInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class TwitchRendererTest extends UnitTestCase
{
    private TwitchRenderer $subject;

    protected bool $resetSingletonInstances = true;

    protected function setUp(): void
    {
        parent::setUp();

        $eventDispatcherMock = $this->getMockBuilder(EventDispatcherInterface::class)->getMock();

        $configurationManagerMock = $this->getMockBuilder(ConfigurationManager::class)
            ->onlyMethods(['getConfiguration'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->subject = new TwitchRenderer($eventDispatcherMock, $configurationManagerMock);
    }

    #[Test]
    public function hasFileRendererInterface(): void
    {
        self::assertInstanceOf(FileRendererInterface::class, $this->subject);
    }

    #[Test]
    public function canRenderWithMatchingMimeTypeReturnsTrue(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['onlineMediaHelpers']['twitch'] = TwitchHelper::class;

        $fileResourceMock = $this->createMock(File::class);
        $fileResourceMock->expects(self::any())->method('getMimeType')->willReturn('video/twitch');
        $fileResourceMock->expects(self::any())->method('getExtension')->willReturn('twitch');

        $result = $this->subject->canRender($fileResourceMock);
        self::assertTrue($result);
    }


    #[Test]
    public function canRenderWithMatchingMimeTypeReturnsFalse(): void
    {
        $fileResourceMock = $this->createMock(File::class);
        $fileResourceMock->expects(self::any())->method('getMimeType')->willReturn('video/twitch');
        $fileResourceMock->expects(self::any())->method('getExtension')->willReturn('twitch');

        $result = $this->subject->canRender($fileResourceMock);
        self::assertFalse($result);
    }

    #[Test]
    #[DataProvider('getPrivacySettingWithExistingConfigReturnsBooleanDataProvider')]
    public function getPrivacySettingWithExistingConfigReturnsBoolean(array $pluginConfig, bool $expected)
    {
        $eventDispatcherMock = $this->getMockBuilder(EventDispatcherInterface::class)->getMock();

        $configurationManagerMock = $this->getMockBuilder(ConfigurationManager::class)
            ->onlyMethods(['getConfiguration'])
            ->disableOriginalConstructor()
            ->getMock();

        $configurationManagerMock
            ->expects(self::atLeastOnce())
            ->method('getConfiguration')
            ->with(ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT)
            ->willReturn($pluginConfig);

        $subject = new TwitchRenderer($eventDispatcherMock, $configurationManagerMock);

        $params = [];
        $methodName = 'getPrivacySetting';
        $result = $this->buildReflectionForProtectedFunction($methodName, $params, $subject);

        self::assertEquals($expected, $result);
    }

    public static function getPrivacySettingWithExistingConfigReturnsBooleanDataProvider(): array
    {
        return [
            'Privacy setting true' => [
                [
                    'plugin.' => [
                        'tx_twitch.' => [
                            'settings.' => [
                                'privacy' => true,
                            ],
                        ],
                    ],
                ],
                true,
            ],
            'Privacy setting false' => [
                [
                    'plugin.' => [
                        'tx_twitch.' => [
                            'settings.' => [
                                'privacy' => false,
                            ],
                        ],
                    ],
                ],
                false,
            ],
            'Privacy setting non-existing' => [
                [],
                false,
            ],
        ];
    }

    #[Test]
    public function renderWihtIframeAndWithoutPrivacyReturnsTwitchHtml(): void
    {
        $iframe = '<iframe src="https://player.twitch.tv/?video=123456&parent=&autoplay=false&muted=false" ';
        $iframe .= 'frameborder="0" allowfullscreen="true" scrolling="no" height="100" width="100"></iframe>';
        $expected = $iframe;

        $fileResourceMock = $this->createMock(File::class);
        $fileResourceMock->expects(self::any())->method('getMimeType')->willReturn('video/tiktok');
        $fileResourceMock->expects(self::any())->method('getExtension')->willReturn('tiktok');
        $fileResourceMock->expects(self::any())->method('getProperty')->with('twitch_html')->willReturn($iframe);

        $videoId = '123456';

        $extensionConfigurationMock = $this->createMock(ExtensionConfiguration::class);
        GeneralUtility::addInstance(ExtensionConfiguration::class, $extensionConfigurationMock);
        $expectedConfiguration = ['display' => 'iframe'];
        $extensionConfigurationMock->method('get')->with('twitch')->willReturn($expectedConfiguration);

        $event = new ModifyTwitchOutputEvent($expected);
        $eventDispatcherMock = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcherMock->expects(self::once())->method('dispatch')->with($event)->willReturn($event);

        $configurationManagerMock = $this->getMockBuilder(ConfigurationManager::class)
            ->onlyMethods(['getConfiguration'])
            ->disableOriginalConstructor()
            ->getMock();

        $tiktokHelperMock = $this->getMockBuilder(TwitchRenderer::class)
            ->setConstructorArgs([$eventDispatcherMock, $configurationManagerMock])
            ->onlyMethods(['getVideoIdFromFile'])
            ->getMock();
        $tiktokHelperMock->method('getVideoIdFromFile')->with($fileResourceMock)->willReturn($videoId);

        $result = $tiktokHelperMock->render($fileResourceMock, 100, 100);
        self::assertSame($expected, $result);
    }

    #[Test]
    public function renderWithIframeAndPrivacyReturnsTwitchHtml(): void
    {
        $iframe = '<iframe src="https://www.twitch.tv" />';
        $expected = '<iframe data-name="script-twitch" data-src="https://player.twitch.tv/?video=123456&parent=&';
        $expected .= 'autoplay=false&muted=false" frameborder="0" allowfullscreen="true" scrolling="no" ';
        $expected .= 'height="100" width="100"></iframe>';

        $fileResourceMock = $this->createMock(File::class);
        $fileResourceMock->expects(self::any())->method('getMimeType')->willReturn('video/twitch');
        $fileResourceMock->expects(self::any())->method('getExtension')->willReturn('twitch');
        $fileResourceMock->expects(self::any())->method('getProperty')->with('twitch_html')->willReturn($iframe);

        $event = new ModifyTwitchOutputEvent($expected);
        $eventDispatcherMock = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcherMock->expects(self::once())->method('dispatch')->with($event)->willReturn($event);

        $configurationManagerMock = $this->getMockBuilder(ConfigurationManager::class)
            ->onlyMethods(['getConfiguration'])
            ->disableOriginalConstructor()
            ->getMock();

        $pluginConfig = [
            'plugin.' => [
                'tx_twitch.' => [
                    'settings.' => [
                        'privacy' => true,
                    ],
                ],
            ],
        ];

        $configurationManagerMock
            ->expects(self::atLeastOnce())
            ->method('getConfiguration')
            ->with(ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT)
            ->willReturn($pluginConfig);

        $videoId = '123456';

        $extensionConfigurationMock = $this->createMock(ExtensionConfiguration::class);
        GeneralUtility::addInstance(ExtensionConfiguration::class, $extensionConfigurationMock);
        $expectedConfiguration = ['display' => 'iframe'];
        $extensionConfigurationMock->method('get')->with('twitch')->willReturn($expectedConfiguration);

        $twitchHelperMock = $this->getMockBuilder(TwitchRenderer::class)
            ->setConstructorArgs([$eventDispatcherMock, $configurationManagerMock])
            ->onlyMethods(['getVideoIdFromFile'])
            ->getMock();
        $twitchHelperMock->method('getVideoIdFromFile')->with($fileResourceMock)->willReturn($videoId);

        $result = $twitchHelperMock->render($fileResourceMock, 100, 100);
        self::assertSame($expected, $result);
    }

    #[Test]
    public function renderWithJavaScriptAndPrivacyReturnsTwitchHtml(): void
    {
        $iframe = '<iframe src="https://www.twitch.tv" />';
        $expected = '<iframe data-name="script-twitch" data-src="https://player.twitch.tv/?video=123456&parent=&';
        $expected .= 'autoplay=false&muted=false" frameborder="0" allowfullscreen="true" scrolling="no" ';
        $expected .= 'height="100" width="100"></iframe>';

        $fileResourceMock = $this->createMock(File::class);
        $fileResourceMock->expects(self::any())->method('getMimeType')->willReturn('video/twitch');
        $fileResourceMock->expects(self::any())->method('getExtension')->willReturn('twitch');
        $fileResourceMock->expects(self::any())->method('getProperty')->with('twitch_html')->willReturn($iframe);

        $event = new ModifyTwitchOutputEvent($expected);
        $eventDispatcherMock = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcherMock->expects(self::once())->method('dispatch')->with($event)->willReturn($event);

        $configurationManagerMock = $this->getMockBuilder(ConfigurationManager::class)
            ->onlyMethods(['getConfiguration'])
            ->disableOriginalConstructor()
            ->getMock();

        $pluginConfig = [
            'plugin.' => [
                'tx_twitch.' => [
                    'settings.' => [
                        'privacy' => true,
                    ],
                ],
            ],
        ];

        $configurationManagerMock
            ->expects(self::atLeastOnce())
            ->method('getConfiguration')
            ->with(ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT)
            ->willReturn($pluginConfig);

        $videoId = '123456';

        $extensionConfigurationMock = $this->createMock(ExtensionConfiguration::class);
        GeneralUtility::addInstance(ExtensionConfiguration::class, $extensionConfigurationMock);
        $expectedConfiguration = ['display' => 'iframe'];
        $extensionConfigurationMock->method('get')->with('twitch')->willReturn($expectedConfiguration);

        $twitchHelperMock = $this->getMockBuilder(TwitchRenderer::class)
            ->setConstructorArgs([$eventDispatcherMock, $configurationManagerMock])
            ->onlyMethods(['getVideoIdFromFile'])
            ->getMock();
        $twitchHelperMock->method('getVideoIdFromFile')->with($fileResourceMock)->willReturn($videoId);

        $result = $twitchHelperMock->render($fileResourceMock, 100, 100);
        self::assertSame($expected, $result);
    }

    #[Test]
    public function renderJavaScriptGeneratesExpectedTwitchEmbedCode(): void
    {
        $videoId = 'testVideo123';
        $width = '800';
        $height = '600';
        $options = [
            'autoplay' => true,
            'muted' => false,
            'time' => '1h15m30s'
        ];

        $pageRendererMock = $this->getMockBuilder(PageRenderer::class)
            ->onlyMethods(['addJsFile'])
            ->disableOriginalConstructor()
            ->getMock();
        $pageRendererMock->method('addJsFile')->with('https://player.twitch.tv/js/embed/v1.js');
        GeneralUtility::setSingletonInstance(PageRenderer::class, $pageRendererMock);

        $params = [$options, $videoId, $width, $height];
        $methodName = 'renderJavaScript';
        $result = $this->buildReflectionForProtectedFunction($methodName, $params, $this->subject);

        self::assertStringContainsString('<div id="twitch-embed', $result);
        self::assertStringContainsString('width: ' . $width, $result);
        self::assertStringContainsString('height: ' . $height, $result);
        self::assertStringContainsString('video: "' . rawurlencode($videoId) . '"', $result);
        self::assertStringContainsString('autoplay: true', $result);
        self::assertStringContainsString('muted: false', $result);
        self::assertStringContainsString('time: "1h15m30s"', $result);
    }


    protected function buildReflectionForProtectedFunction(
        string $methodName,
        array $params,
        TwitchRenderer $subject
    ): mixed {
        $reflectionCalendar = new \ReflectionClass($subject);
        $method = $reflectionCalendar->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($subject, $params);
    }
}
