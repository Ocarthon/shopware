<?php declare(strict_types=1);

namespace Shopware\Tests\Integration\Core\Framework\Logging;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Monolog\Handler\TestHandler;
use Monolog\Level;
use Monolog\Logger;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\MailTemplate\Service\Event\MailErrorEvent;
use Shopware\Core\Content\Test\Flow\TestFlowBusinessEvent;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Event\FlowLogEvent;
use Shopware\Core\Framework\Log\LoggingService;
use Shopware\Core\Framework\Test\Logging\Event\LogAwareTestFlowEvent;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Test\Stub\Doctrine\TestExceptionFactory;

/**
 * @internal
 */
class LoggingServiceTest extends TestCase
{
    use IntegrationTestBehaviour;

    protected Context $context;

    protected function setUp(): void
    {
        parent::setUp();

        $this->context = Context::createDefaultContext();
    }

    /**
     * @throws Exception
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        $connection = static::getContainer()->get(Connection::class);
        $connection->executeStatement('DELETE FROM `log_entry`');
    }

    public function testWriteFlowEvents(): void
    {
        $handler = new TestHandler();
        $logger = new Logger('testlogger', [$handler]);

        $service = new LoggingService('test', $logger);

        $service->logFlowEvent(
            new FlowLogEvent(TestFlowBusinessEvent::EVENT_NAME, new TestFlowBusinessEvent($this->context))
        );

        $records = $handler->getRecords();

        static::assertCount(1, $records);
        $testRecord = $records[0];

        static::assertEquals(TestFlowBusinessEvent::EVENT_NAME, $testRecord->message);
        static::assertEquals('test', $testRecord->context['environment']);
        static::assertEquals(Level::Debug, $testRecord->level);
        static::assertEmpty($testRecord->context['additionalData']);
    }

    public function testWriteMailSendLogEvents(): void
    {
        $handler = new TestHandler();
        $logger = new Logger('testlogger', [$handler]);

        $service = new LoggingService('test', $logger);

        $service->logFlowEvent(
            new FlowLogEvent(TestFlowBusinessEvent::EVENT_NAME, new MailErrorEvent($this->context, Level::Error, TestExceptionFactory::createException('test')))
        );

        $records = $handler->getRecords();

        static::assertCount(1, $records);
        $testRecord = $records[0];

        static::assertEquals(MailErrorEvent::NAME, $testRecord->message);
        static::assertEquals('test', $testRecord->context['environment']);
        static::assertEquals(Level::Error, $testRecord->level);
    }

    #[Depends('testWriteFlowEvents')]
    public function testWriteLogAwareFlowEvent(): void
    {
        $handler = new TestHandler();
        $logger = new Logger('testlogger', [$handler]);

        $service = new LoggingService(
            'test',
            $logger
        );

        $service->logFlowEvent(
            new FlowLogEvent(LogAwareTestFlowEvent::EVENT_NAME, new LogAwareTestFlowEvent($this->context))
        );

        $records = $handler->getRecords();
        static::assertCount(1, $records);
        $testRecord = $records[0];

        static::assertEquals(Level::Emergency, $testRecord->level);
        static::assertNotEmpty($testRecord->context['additionalData']);
        static::assertArrayHasKey('awesomekey', $testRecord->context['additionalData']);
        static::assertEquals('awesomevalue', $testRecord->context['additionalData']['awesomekey']);
    }
}
