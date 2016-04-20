<?php

namespace CultuurNet\BroadwayAMQP;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainEventStream;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\EventHandling\EventBusInterface;
use CultuurNet\Deserializer\DeserializerLocatorInterface;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use ValueObjects\Identity\UUID;
use ValueObjects\String\String as StringLiteral;

/**
 * Forwards messages coming in via AMQP to an event bus.
 */
class EventBusForwardingConsumer implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var AMQPStreamConnection
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
     * @var EventBusInterface
     */
    private $eventBus;

    /**
     * @var DeserializerLocatorInterface
     */
    private $deserializerLocator;

    /**
     * @var AMQPChannel
     */
    private $channel;

    /**
     * Seconds to delay the actual consumption of the message after it arrived.
     *
     * @var int
     */
    private $delay = 0;

    /**
     * @param AMQPStreamConnection $connection
     * @param EventBusInterface $eventBus
     * @param DeserializerLocatorInterface $deserializerLocator
     * @param StringLiteral $consumerTag
     * @param StringLiteral $exchangeName
     * @param StringLiteral $queueName
     * @param int $delay
     */
    public function __construct(
        AMQPStreamConnection $connection,
        EventBusInterface $eventBus,
        DeserializerLocatorInterface $deserializerLocator,
        StringLiteral $consumerTag,
        StringLiteral $exchangeName,
        StringLiteral $queueName,
        $delay = 0
    ) {
        $this->connection = $connection;
        $this->channel = $connection->channel();
        $this->channel->basic_qos(0, 4, true);

        $this->eventBus = $eventBus;

        $this->deserializerLocator = $deserializerLocator;

        $this->queueName = $queueName;
        $this->consumerTag = $consumerTag;
        $this->exchangeName = $exchangeName;

        $this->delay = $delay;

        $this->declareQueue();
        $this->registerConsumeCallback();
    }

    private function delayIfNecessary()
    {
        if ($this->delay > 0) {
            sleep($this->delay);
        }
    }

    /**
     * @param AMQPMessage $message
     */
    public function consume(AMQPMessage $message)
    {
        $context = [];

        if ($message->has('correlation_id')) {
            $context['correlation_id'] = $message->get('correlation_id');
        }

        if ($this->logger) {
            $this->logger->info(
                'received message with content-type ' . $message->get(
                    'content_type'
                ),
                $context
            );
        }

        $contentType = new StringLiteral($message->get('content_type'));

        try {
            $deserializer = $this->deserializerLocator->getDeserializerForContentType(
                $contentType
            );
            $domainMessage = $deserializer->deserialize(
                new StringLiteral($message->body)
            );

            // If the deserializer did not return a DomainMessage yet, then
            // consider the returned value as the payload, and wrap it in a
            // DomainMessage.
            if (!$domainMessage instanceof DomainMessage) {
                $domainMessage = new DomainMessage(
                    UUID::generateAsString(),
                    0,
                    new Metadata($context),
                    $domainMessage,
                    DateTime::now()
                );
            }

            $this->delayIfNecessary();

            if ($this->logger) {
                $this->logger->info(
                    'passing on message to event bus',
                    $context
                );
            }

            $this->eventBus->publish(
                new DomainEventStream([$domainMessage])
            );

        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error(
                    $e->getMessage(),
                    $context + ['exception' => $e]
                );
            }

            $message->delivery_info['channel']->basic_reject(
                $message->delivery_info['delivery_tag'],
                false
            );

            if ($this->logger) {
                $this->logger->info(
                    'message rejected',
                    $context
                );
            }

            return;
        }

        $message->delivery_info['channel']->basic_ack(
            $message->delivery_info['delivery_tag']
        );

        if ($this->logger) {
            $this->logger->info(
                'message acknowledged',
                $context
            );
        }
    }

    protected function declareQueue()
    {
        $this->channel->queue_declare(
            (string) $this->queueName,
            $passive = false,
            $durable = true,
            $exclusive = false,
            $autoDelete = false
        );

        $this->channel->queue_bind(
            $this->queueName,
            $this->exchangeName,
            $routingKey = '#'
        );
    }

    protected function registerConsumeCallback()
    {
        $this->channel->basic_consume(
            $this->queueName,
            $consumerTag = (string) $this->consumerTag,
            $noLocal = false,
            $noAck = false,
            $exclusive = false,
            $noWait = false,
            [$this, 'consume']
        );
    }

    /**
     * @return AMQPStreamConnection
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * @return AMQPChannel
     */
    public function getChannel()
    {
        return $this->channel;
    }
}
