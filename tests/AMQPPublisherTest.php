<?php

namespace CultuurNet\BroadwayAMQP;

use Broadway\Domain\DateTime as BroadwayDateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use CultuurNet\BroadwayAMQP\Dummies\DummyAlwaysSatisfied;
use CultuurNet\BroadwayAMQP\Dummies\DummyEvent;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;

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
     * @var DomainMessage
     */
    private $domainMessage;
    
    protected function setUp()
    {
        /** @var AMQPStreamConnection $connection */
        $connection = new AMQPStreamConnection(
            'host',
            'port',
            'user',
            'password',
            'vhost'
        );

        $this->amqpChannel = new AMQPChannel($connection);

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
        $amqpPublisher = new AMQPPublisher(
            $this->amqpChannel,
            null,
            new DummyAlwaysSatisfied()
        );

        $amqpPublisher = $amqpPublisher->withContentType(
            DummyEvent::class,
            'application/vnd.cultuurnet.udb3-events.dummy-event+json'
        );

        $this->amqpChannel->expects($this->once())
            ->method('basic_publish');

        $amqpPublisher->handle($this->domainMessage);
    }

    /**
     * @test
     */
    public function it_does_not_publish_a_domain_message_when_specification_is_not_satisfied()
    {
        $this->assertTrue(false);
    }

    /**
     * @test
     */
    public function it_does_create_an_amqp_message()
    {
        $this->assertTrue(false);
    }

    /**
     * @test
     */
    public function it_sets_the_content_type_for_a_given_payload_class()
    {
        $this->assertTrue(false);
    }

    /**
     * @test
     */
    public function it_throws_runtime_exception_when_payload_is_not_serializable()
    {
        $this->assertTrue(false);
    }

    /**
     * @test
     */
    public function it_throws_runtime_exception_for_a_satisfied_specification_with_missing_content_type()
    {
        $amqpPublisher = new AMQPPublisher(
            $this->amqpChannel,
            null,
            new DummyAlwaysSatisfied()
        );

        $this->setExpectedException(
            \RuntimeException::class,
            'Unable to find the content type of CultuurNet\BroadwayAMQP\Dummies\DummyEvent'
        );

        $amqpPublisher->handle($this->domainMessage);
    }

    /**
     * @test
     */
    public function it_throws_runtime_exception_when_setting_the_same_content_type()
    {
        $this->assertTrue(false);
    }

    /**
     * @test
     */
    public function it_throws_invalid_argument_exception_when_setting_content_type_and_payload_class_is_not_a_string()
    {
        $this->assertTrue(false);
    }

    /**
     * @test
     */
    public function it_throws_invalid_argument_exception_when_setting_content_type_and_content_type_is_not_a_string()
    {
        $this->assertTrue(false);
    }
}