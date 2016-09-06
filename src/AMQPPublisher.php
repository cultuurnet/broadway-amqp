<?php

namespace CultuurNet\BroadwayAMQP;

use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventListenerInterface;
use CultuurNet\BroadwayAMQP\DomainMessage\SpecificationInterface;
use CultuurNet\BroadwayAMQP\Message\BodyFactoryInterface;
use CultuurNet\BroadwayAMQP\Message\PayloadOnlyBodyFactory;
use CultuurNet\BroadwayAMQP\Message\PropertiesFactoryInterface;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

class AMQPPublisher implements EventListenerInterface
{
    use LoggerAwareTrait;

    /**
     * @var string
     */
    private $exchange;

    /**
     * @var SpecificationInterface
     */
    private $domainMessageSpecification;

    /**
     * @var AMQPChannel
     */
    private $channel;

    /**
     * @var PropertiesFactoryInterface
     */
    private $propertiesFactory;

    /**
     * @var BodyFactoryInterface
     */
    private $bodyFactory;

    /**
     * @param AMQPChannel $channel
     * @param $exchange
     * @param SpecificationInterface $domainMessageSpecification
     * @param PropertiesFactoryInterface $propertiesFactory
     * @param BodyFactoryInterface $bodyFactory
     */
    public function __construct(
        AMQPChannel $channel,
        $exchange,
        SpecificationInterface $domainMessageSpecification,
        PropertiesFactoryInterface $propertiesFactory,
        BodyFactoryInterface $bodyFactory = null
    ) {
        $this->channel = $channel;
        $this->exchange = $exchange;
        $this->domainMessageSpecification = $domainMessageSpecification;
        $this->logger = new NullLogger();
        $this->propertiesFactory = $propertiesFactory;

        if (!$bodyFactory) {
            $bodyFactory = new PayloadOnlyBodyFactory();
        }
        $this->bodyFactory = $bodyFactory;
    }

    /**
     * @inheritdoc
     */
    public function handle(DomainMessage $domainMessage)
    {
        if ($this->domainMessageSpecification->isSatisfiedBy($domainMessage)) {
            $this->publishWithAMQP($domainMessage);
        } else {
            $this->logger->warning('message was skipped by specification ' . get_class($this->domainMessageSpecification));
        }
    }

    /**
     * @param DomainMessage $domainMessage
     */
    private function publishWithAMQP(DomainMessage $domainMessage)
    {
        $payload = $domainMessage->getPayload();
        $eventClass = get_class($payload);
        $this->logger->info("publishing message with event type {$eventClass} to exchange {$this->exchange}");

        $this->channel->basic_publish(
            new AMQPMessage(
                $this->bodyFactory->createBody($domainMessage),
                $this->propertiesFactory->createProperties($domainMessage)
            ),
            $this->exchange
        );
    }
}
