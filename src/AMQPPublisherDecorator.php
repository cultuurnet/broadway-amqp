<?php

namespace CultuurNet\BroadwayAMQP;

use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventListenerInterface as AMQPPublisherInterface;

abstract class AMQPPublisherDecorator implements AMQPPublisherInterface
{
    /**
     * @var AMQPPublisherInterface
     */
    private $amqpPublisher;

    /**
     * AMQPPublisherDecorator constructor.
     * @param AMQPPublisherInterface $amqpPublisher
     */
    public function __construct(AMQPPublisherInterface $amqpPublisher)
    {
        $this->amqpPublisher = $amqpPublisher;
    }

    /**
     * @inheritdoc
     */
    public function handle(DomainMessage $domainMessage)
    {
        $this->amqpPublisher->handle($domainMessage);
    }
}
