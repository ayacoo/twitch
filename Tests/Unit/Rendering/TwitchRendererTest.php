<?php

declare(strict_types=1);

namespace Ayacoo\Tiktok\Tests\Unit\Rendering;

use Ayacoo\Twitch\Event\ModifyTwitchOutputEvent;
use Ayacoo\Twitch\Helper\TwitchHelper;
use Ayacoo\Twitch\Rendering\TwitchRenderer;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Rendering\FileRendererInterface;
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

    /**
     * @test
     */
    public function hasFileRendererInterface(): void
    {
        self::assertInstanceOf(FileRendererInterface::class, $this->subject);
    }

    /**
     * @test
     */
    public function canRenderWithMatchingMimeTypeReturnsTrue(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['onlineMediaHelpers']['twitch'] = TwitchHelper::class;

        $fileResourceMock = $this->createMock(File::class);
        $fileResourceMock->expects(self::any())->method('getMimeType')->willReturn('video/twitch');
        $fileResourceMock->expects(self::any())->method('getExtension')->willReturn('twitch');

        $result = $this->subject->canRender($fileResourceMock);
        self::assertTrue($result);
    }

    protected function buildReflectionForProtectedFunction(
        string $methodName,
        array $params,
        TiktokRenderer $subject
    ): mixed {
        $reflectionCalendar = new \ReflectionClass($subject);
        $method = $reflectionCalendar->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($subject, $params);
    }
}
