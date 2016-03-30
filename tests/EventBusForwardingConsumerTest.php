<?php

namespace CultuurNet\BroadwayAMQP;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainEventStream;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\EventHandling\EventBusInterface;
use CultuurNet\Deserializer\DeserializerInterface;
use CultuurNet\Deserializer\DeserializerLocatorInterface;
use PhpAmqpLib\Channel\AbstractChannel;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;
use ValueObjects\Identity\UUID;
use ValueObjects\String\String as StringLiteral;

class EventBusForwardingConsumerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AMQPStreamConnection|\PHPUnit_Framework_MockObject_MockObject
     */
    private $connection;

    /**
     * @var StringLiteral
     */
    private $queueName;

    /**
     * @var StringLiteral
     */
    private $exchangeName;

    /**
     * @var StringLiteral
     */
    private $consumerTag;

    /**
     * @var EventBusInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $eventBus;

    /**
     * @var DeserializerLocatorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $deserializerLocator;

    /**
     * @var AbstractChannel|\PHPUnit_Framework_MockObject_MockObject
     */
    private $channel;

    /**
     * Seconds to delay the actual consumption of the message after it arrived.
     *
     * @var int
     */
    private $delay = 0;

    /**
     * @var EventBusForwardingConsumer
     */
    private $eventBusForwardingConsumer;

    /**
     * @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $logger;

    /**
     * @var DeserializerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $deserializer;


    public function setUp()
    {
        $this->connection = $this->getMock(
            AMQPStreamConnection::class,
            array(),
            array(),
            'AMQPStreamConnection',
            false
        );

        $this->queueName = new StringLiteral('my-queue');
        $this->exchangeName = new StringLiteral('my-exchange');
        $this->consumerTag = new StringLiteral('my-tag');
        $this->eventBus = $this->getMock(EventBusInterface::class);
        $this->deserializerLocator = $this->getMock(DeserializerLocatorInterface::class);
        $this->channel = $this->getMockForAbstractClass(
            AbstractChannel::class,
            array(),
            'AMQPChannel',
            false
        );

        $this->connection->expects($this->any())
            ->method('channel')
            ->willReturn($this->channel);

        $this->eventBusForwardingConsumer = new EventBusForwardingConsumer(
            $this->connection,
            $this->eventBus,
            $this->deserializerLocator,
            $this->consumerTag,
            $this->exchangeName,
            $this->queueName
        );

        /** @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject $logger */
        $this->logger = $this->getMock(LoggerInterface::class);
        $this->eventBusForwardingConsumer->setLogger($this->logger);

        $this->deserializer = $this->getMock(DeserializerInterface::class);
    }

    /**
     * @test
     */
    public function it_can_get_the_connection()
    {
        $this->channel->expects($this->once())
            ->method('basic_qos')
            ->with(0, 4, true);

        $eventBusForwardingConsumer = new EventBusForwardingConsumer(
            $this->connection,
            $this->eventBus,
            $this->deserializerLocator,
            $this->consumerTag,
            $this->exchangeName,
            $this->queueName
        );

        $expectedConnection = $this->connection;

        $this->assertEquals($expectedConnection, $eventBusForwardingConsumer->getConnection());
    }

    /**
     * @test
     */
    public function it_can_publish_the_message_on_the_event_bus()
    {
        $context = [];
        $context['correlation_id'] = new StringLiteral('my-correlation-id-123');

        $expectedDomainMessage = new DomainMessage(
            '',
            0,
            new Metadata($context),
            '',
            DateTime::now()
        );

        $this->eventBus->expects($this->once())
            ->method('publish');
            //->with(new DomainEventStream([$expectedDomainMessage]));

        $this->deserializerLocator->expects($this->once())
            ->method('getDeserializerForContentType')
            ->with(new StringLiteral('application/vnd.cultuurnet.udb3-events.dummy-event+json'))
            ->willReturn($this->deserializer);

        $this->deserializer->expects($this->once())
            ->method('deserialize')
            ->with(new StringLiteral(''))
            ->willReturn('');

        $this->channel->expects($this->once())
            ->method('basic_ack')
            ->with('my-delivery-tag');

        $messageProperties = [
            'content_type' => 'application/vnd.cultuurnet.udb3-events.dummy-event+json',
            'correlation_id' => 'my-correlation-id-123'
        ];

        $messageBody = '';

        $message = new AMQPMessage($messageBody, $messageProperties);
        $message->delivery_info['channel'] = $this->channel;
        $message->delivery_info['delivery_tag'] = 'my-delivery-tag';

        $this->eventBusForwardingConsumer->consume($message);
    }

    /**
     * @test
     */
    public function it_logs_messages_when_consuming()
    {
        $context = [];
        $context['correlation_id'] = new StringLiteral('my-correlation-id-123');
        
        $this->logger
            ->expects($this->at(0))
            ->method('info')
            ->with(
                'received message with content-type application/vnd.cultuurnet.udb3-events.dummy-event+json',
                $context
            );

        $this->logger
            ->expects($this->at(1))
            ->method('info')
            ->with(
                'passing on message to event bus',
                $context
            );
        
        $this->logger
            ->expects($this->at(2))
            ->method('info')
            ->with(
                'message acknowledged',
                $context
            );

        $this->deserializerLocator->expects($this->once())
            ->method('getDeserializerForContentType')
            ->with(new StringLiteral('application/vnd.cultuurnet.udb3-events.dummy-event+json'))
            ->willReturn($this->deserializer);
        
        $this->deserializer->expects($this->once())
            ->method('deserialize')
            ->with(new StringLiteral(''));

        $this->channel->expects($this->once())
            ->method('basic_ack')
            ->with('my-delivery-tag');

        $messageProperties = [
            'content_type' => 'application/vnd.cultuurnet.udb3-events.dummy-event+json',
            'correlation_id' => 'my-correlation-id-123'
        ];

        $messageBody = '';

        $message = new AMQPMessage($messageBody, $messageProperties);
        $message->delivery_info['channel'] = $this->channel;
        $message->delivery_info['delivery_tag'] = 'my-delivery-tag';

        $this->eventBusForwardingConsumer->consume($message);

    }
}
