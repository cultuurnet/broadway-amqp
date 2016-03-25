<?php

namespace CultuurNet\BroadwayAMQP;

use Broadway\EventHandling\EventBusInterface;
use CultuurNet\Deserializer\DeserializerLocatorInterface;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use Psr\Log\LoggerInterface;
use ValueObjects\Number\Natural;
use ValueObjects\String\String as StringLiteral;

class EventBusForwardingConsumerFactory
{
    /**
     * Delay the consumption of UDB2 updates with some seconds to prevent a
     * race condition with the UDB3 worker. Modifications initiated by
     * commands in the UDB3 queue worker need to finish before their
     * counterpart UDB2 update is processed.
     *
     * @var Natural
     */
    protected $executionDelay;

    /**
     * @var array
     */
    protected $connectionConfig;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var DeserializerLocatorInterface
     */
    protected $deserializerLocator;

    /**
     * @var EventBusInterface
     */
    protected $eventBus;

    /**
     * EventBusForwardingConsumerFactory constructor.
     */
    public function __construct(
        Natural $executionDelay,
        $connectionConfig,
        LoggerInterface $logger,
        DeserializerLocatorInterface $deserializerLocator,
        EventBusInterface $eventBus
    ) {
        $this->executionDelay = $executionDelay;
        $this->connectionConfig = $connectionConfig;
        $this->logger = $logger;
        $this->deserializerLocator = $deserializerLocator;
        $this->eventBus = $eventBus;
    }

    /**
     * @param StringLiteral $exchange
     * @param StringLiteral $queue
     * @return EventBusForwardingConsumer
     */
    public function create(StringLiteral $exchange, StringLiteral $queue)
    {
        $connection = new AMQPStreamConnection(
            $this->connectionConfig['host'],
            $this->connectionConfig['port'],
            $this->connectionConfig['user'],
            $this->connectionConfig['password'],
            $this->connectionConfig['vhost']
        );
        
        $eventBusForwardingConsumer = new EventBusForwardingConsumer(
            $connection,
            $this->eventBus,
            $this->deserializerLocator,
            new StringLiteral($this->connectionConfig['consumer_tag']),
            $exchange,
            $queue,
            $this->executionDelay->toNative()
        );

        $eventBusForwardingConsumer->setLogger($this->logger);

        return $eventBusForwardingConsumer;
    }
}
