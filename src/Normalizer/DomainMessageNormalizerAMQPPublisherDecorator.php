<?php

namespace CultuurNet\BroadwayAMQP\Normalizer;

use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventListenerInterface as AMQPPublisherInterface;
use CultuurNet\BroadwayAMQP\AMQPPublisherDecorator;

class DomainMessageNormalizerAMQPPublisherDecorator extends AMQPPublisherDecorator
{
    /**
     * @var DomainMessageNormalizerInterface
     */
    private $domainMessageNormalizer;

    /**
     * DomainMessageNormalizerDecorator constructor.
     * @param AMQPPublisherInterface $amqpPublisher
     * @param DomainMessageNormalizerInterface $domainMessageNormalizer
     */
    public function __construct(
        AMQPPublisherInterface $amqpPublisher,
        DomainMessageNormalizerInterface $domainMessageNormalizer
    ) {
        parent::__construct($amqpPublisher);
        
        $this->domainMessageNormalizer = $domainMessageNormalizer;
    }

    /**
     * @inheritdoc
     */
    public function handle(DomainMessage $domainMessage)
    {
        $domainMessages = $this->domainMessageNormalizer->normalize($domainMessage);

        foreach ($domainMessages as $domainMessage) {
            parent::handle($domainMessage);
        }
    }
}
