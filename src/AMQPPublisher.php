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
     * @var string[]
     */
    private $payloadClassToContentTypeMap;

    /**
     * @param AMQPChannel $channel
     * @param $exchange
     * @param SpecificationInterface $domainMessageSpecification
     */
    public function __construct(
        AMQPChannel $channel,
        $exchange,
        SpecificationInterface $domainMessageSpecification
    ) {
        $this->channel = $channel;
        $this->exchange = $exchange;
        $this->domainMessageSpecification = $domainMessageSpecification;
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

        if (isset($this->payloadClassToContentTypeMap[$payloadClass])) {
            return $this->payloadClassToContentTypeMap[$payloadClass];
        }

        throw new \RuntimeException(
            'Unable to find the content type of ' . $payloadClass
        );
    }

    /**
     * @param string $payloadClass
     * @param string $contentType
     * @return static
     */
    public function withContentType($payloadClass, $contentType)
    {
        // TODO: Pass in ContentTypeLookupInterface
        $c = clone $this;
        $c->setContentType($payloadClass, $contentType);
        return $c;
    }

    /**
     * @param string $payloadClass
     * @param string $contentType
     */
    private function setContentType($payloadClass, $contentType)
    {
        if (!is_string($payloadClass)) {
            throw new \InvalidArgumentException(
                'Value for argument payloadClass should be a string'
            );
        }

        if (!is_string($contentType)) {
            throw new \InvalidArgumentException(
                'Value for argument contentType should be a string'
            );
        }

        if (isset($this->payloadClassToContentTypeMap[$payloadClass])) {
            $currentContentType = $this->payloadClassToContentTypeMap[$payloadClass];
            throw new \InvalidArgumentException(
                'Content type for class ' . $payloadClass . ' was already set to ' . $currentContentType
            );
        }
        $this->payloadClassToContentTypeMap[$payloadClass] = $contentType;
    }
}
