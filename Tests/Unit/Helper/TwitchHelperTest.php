<?php

declare(strict_types=1);

namespace Ayacoo\Twitch\Tests\Unit\Helper;

use Ayacoo\Twitch\Helper\TwitchHelper;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\OnlineMedia\Helpers\AbstractOEmbedHelper;
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

    private function buildReflectionForProtectedFunction(string $methodName, array $params)
    {
        $reflectionCalendar = new \ReflectionClass($this->subject);
        $method = $reflectionCalendar->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($this->subject, $params);
    }
}
