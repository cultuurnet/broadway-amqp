<?php

namespace CultuurNet\BroadwayAMQP;

use Broadway\Domain\DateTime as BroadwayDateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use CultuurNet\BroadwayAMQP\Dummies\DummyAlwaysSatisfied;
use CultuurNet\BroadwayAMQP\Dummies\DummyEvent;
use CultuurNet\BroadwayAMQP\Dummies\DummyEventNotSerializable;
use CultuurNet\BroadwayAMQP\Dummies\DummyNeverSatisfied;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;

/**
 * Class AMQPPublisherTest
 * @package CultuurNet\BroadwayAMQP
 */
class AMQPPublisherTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AMQPChannel|\PHPUnit_Framework_MockObject_MockObject
     */
    private $amqpChannel;

    /**
     * @var AMQPPublisher
     */
    private $amqpPublisher;

    /**
     * @var DomainMessage
     */
    private $domainMessage;
    
    protected function setUp()
    {
        $this->amqpChannel = $this->getMock(
            AMQPChannel::class,
            array(),
            array(),
            "AMQPChannel",
            false
        );

        $this->amqpPublisher = new AMQPPublisher(
            $this->amqpChannel,
            null,
            new DummyAlwaysSatisfied()
        );

        $this->domainMessage = new DomainMessage(
            'F68E71A1-DBB0-4542-AEE5-BD937E095F74',
            2,
            new Metadata(),
            new DummyEvent(
                'F68E71A1-DBB0-4542-AEE5-BD937E095F74',
                'test 123 456'
            ),
            BroadwayDateTime::fromString('2015-01-02T08:40:00+0100')
        );
    }

    /**
     * @test
     */
    public function it_does_publish_a_domain_message_when_specification_is_satisfied()
    {
        $amqpPublisher = $this->amqpPublisher->withContentType(
            DummyEvent::class,
            'application/vnd.cultuurnet.udb3-events.dummy-event+json'
        );

        $expectedBody = '{"id":"F68E71A1-DBB0-4542-AEE5-BD937E095F74","content":"test 123 456"}';
        $expectedProperties = [
            "content_type" => "application/vnd.cultuurnet.udb3-events.dummy-event+json",
            "correlation_id" => "F68E71A1-DBB0-4542-AEE5-BD937E095F74-2"
        ];

        $expectedMessage = new AMQPMessage($expectedBody, $expectedProperties);

        $this->amqpChannel->expects($this->once())
            ->method('basic_publish')
            ->with($expectedMessage, null, null);

        $amqpPublisher->handle($this->domainMessage);
    }

    /**
     * @test
     */
    public function it_does_not_publish_a_domain_message_when_specification_is_not_satisfied()
    {
        $amqpPublisher = new AMQPPublisher(
            $this->amqpChannel,
            null,
            new DummyNeverSatisfied()
        );

        $amqpPublisher = $amqpPublisher->withContentType(
            DummyEvent::class,
            'application/vnd.cultuurnet.udb3-events.dummy-event+json'
        );

        $this->amqpChannel->expects($this->never())
            ->method('basic_publish');

        $amqpPublisher->handle($this->domainMessage);
    }

    /**
     * @test
     */
    public function it_throws_runtime_exception_when_payload_is_not_serializable()
    {
        $domainMessage = new DomainMessage(
            'F68E71A1-DBB0-4542-AEE5-BD937E095F74',
            2,
            new Metadata(),
            new DummyEventNotSerializable(
                'F68E71A1-DBB0-4542-AEE5-BD937E095F74',
                'test 123 456'
            ),
            BroadwayDateTime::fromString('2015-01-02T08:40:00+0100')
        );

        $amqpPublisher = $this->amqpPublisher->withContentType(
            DummyEventNotSerializable::class,
            'application/vnd.cultuurnet.udb3-events.dummy-event-not-serializable+json'
        );

        $this->setExpectedException(
            \RuntimeException::class,
            'Unable to serialize CultuurNet\BroadwayAMQP\Dummies\DummyEventNotSerializable'
        );

        $amqpPublisher->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_throws_runtime_exception_for_a_satisfied_specification_with_missing_content_type()
    {
        $this->setExpectedException(
            \RuntimeException::class,
            'Unable to find the content type of CultuurNet\BroadwayAMQP\Dummies\DummyEvent'
        );

        $this->amqpPublisher->handle($this->domainMessage);
    }

    /**
     * @test
     */
    public function it_throws_runtime_exception_when_setting_the_same_content_type()
    {
        $amqpPublisher = $this->amqpPublisher->withContentType(
            DummyEvent::class,
            'application/vnd.cultuurnet.udb3-events.dummy-event+json'
        );

        $this->setExpectedException(
            \InvalidArgumentException::class
        );

        $amqpPublisher->withContentType(
            DummyEvent::class,
            'application/vnd.cultuurnet.udb3-events.dummy-event+json'
        );
    }

    /**
     * @test
     */
    public function it_throws_invalid_argument_exception_when_payload_class_is_not_a_string()
    {
        $this->setExpectedException(
            \InvalidArgumentException::class,
            'Value for argument payloadClass should be a string'
        );

        $this->amqpPublisher->withContentType(
            1,
            'application/vnd.cultuurnet.udb3-events.dummy-event+json'
        );
    }

    /**
     * @test
     */
    public function it_throws_invalid_argument_exception_when_content_type_is_not_a_string()
    {
        $this->setExpectedException(
            \InvalidArgumentException::class,
            'Value for argument contentType should be a string'
        );

        $this->amqpPublisher->withContentType(
            DummyEvent::class,
            1
        );
    }

    /**
     * @test
     */
    public function it_logs_a_message_when_publishing()
    {
        $amqpPublisher = $this->amqpPublisher->withContentType(
            DummyEvent::class,
            'application/vnd.cultuurnet.udb3-events.dummy-event+json'
        );

        /** @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject $logger */
        $logger = $this->getMock(LoggerInterface::class);
        $amqpPublisher->setLogger($logger);

        $logger
            ->expects($this->once())
            ->method('info')
            ->with(
                'publishing message with event type CultuurNet\BroadwayAMQP\Dummies\DummyEvent to exchange '
            );

        $amqpPublisher->handle($this->domainMessage);
    }

    /**
     * @test
     */
    public function it_logs_a_message_when_specification_is_not_satisfied()
    {
        $amqpPublisher = new AMQPPublisher(
            $this->amqpChannel,
            null,
            new DummyNeverSatisfied()
        );

        $amqpPublisher = $amqpPublisher->withContentType(
            DummyEvent::class,
            'application/vnd.cultuurnet.udb3-events.dummy-event+json'
        );

        /** @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject $logger */
        $logger = $this->getMock(LoggerInterface::class);
        $amqpPublisher->setLogger($logger);

        $logger
            ->expects($this->once())
            ->method('warning')
            ->with(
                'message was skipped by specification CultuurNet\BroadwayAMQP\Dummies\DummyNeverSatisfied'
            );

        $amqpPublisher->handle($this->domainMessage);
    }
}
