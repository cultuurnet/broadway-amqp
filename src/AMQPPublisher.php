<?php

namespace CultuurNet\BroadwayAMQP;

use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventListenerInterface;
use Broadway\Serializer\SerializableInterface;
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
     * @var ContentTypeLookup
     */
    private $contentTypeLookup;

    /**
     * @param AMQPChannel $channel
     * @param $exchange
     * @param SpecificationInterface $domainMessageSpecification
     * @param ContentTypeLookupInterface $contentTypeLookup
     */
    public function __construct(
        AMQPChannel $channel,
        $exchange,
        SpecificationInterface $domainMessageSpecification,
        ContentTypeLookupInterface $contentTypeLookup
    ) {
        $this->channel = $channel;
        $this->exchange = $exchange;
        $this->domainMessageSpecification = $domainMessageSpecification;
        $this->contentTypeLookup = $contentTypeLookup;
        $this->logger = new NullLogger();
    }

    /**
     * @inheritdoc
     * @throws \RuntimeException
     */
    public function handle(DomainMessage $domainMessage)
    {
        if ($this->domainMessageSpecification->isSatisfiedBy($domainMessage)) {
            $this->publishWithAMQP($domainMessage);
            return;
        }

        $this->logger->warning('message was skipped by specification ' . get_class($this->domainMessageSpecification));
    }

    /**
     * @param DomainMessage $domainMessage
     */
    private function publishWithAMQP(DomainMessage $domainMessage)
    {
        $key = null;

        $message = $this->createAMQPMessage($domainMessage);

        $payload = $domainMessage->getPayload();
        $eventClass = get_class($payload);

        $this->logger->info(
            'publishing message with event type ' . $eventClass . ' to exchange ' . $this->exchange
        );

        $this->channel->basic_publish(
            $message,
            $this->exchange,
            $key
        );
    }

    /**
     * @param DomainMessage $domainMessage
     * @return AMQPMessage
     */
    private function createAMQPMessage(DomainMessage $domainMessage)
    {
        $body = $this->createAMQPBody($domainMessage);
        $properties = $this->createAMQPProperties($domainMessage);

        return new AMQPMessage($body, $properties);
    }

    /**
     * @param DomainMessage $domainMessage
     * @return string
     */
    private function createAMQPBody(DomainMessage $domainMessage)
    {
        $payload = $domainMessage->getPayload();

        if ($payload instanceof SerializableInterface) {
            return json_encode(
                $payload->serialize()
            );
        }

        throw new \RuntimeException(
            'Unable to serialize ' . get_class($payload)
        );
    }

    /**
     * @param DomainMessage $domainMessage
     * @return array
     */
    private function createAMQPProperties(DomainMessage $domainMessage)
    {
        $properties = [];

        $properties['content_type'] = $this->getContentType($domainMessage);
        $properties['correlation_id'] = $this->getCorrelationId($domainMessage);

        return $properties;
    }

    /**
     * @param DomainMessage $domainMessage
     * @return string
     */
    private function getCorrelationId(DomainMessage $domainMessage)
    {
        return $domainMessage->getId() . '-' . $domainMessage->getPlayhead();
    }

    /**
     * @param DomainMessage $domainMessage
     * @return string
     */
    private function getContentType(DomainMessage $domainMessage)
    {
        $payload = $domainMessage->getPayload();
        $payloadClass = get_class($payload);

        return $this->contentTypeLookup->getContentType($payloadClass);
    }
}
