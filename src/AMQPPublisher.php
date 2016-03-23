<?php

namespace CultuurNet\BroadwayAMQP;

use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventListenerInterface;
use Broadway\Serializer\SerializableInterface;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;

class AMQPPublisher implements EventListenerInterface
{
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
    }

    /**
     * @inheritdoc
     * @throws \RuntimeException
     */
    public function handle(DomainMessage $domainMessage)
    {
        if ($this->domainMessageSpecification->isSatisfiedBy($domainMessage)) {
            $this->publishWithAMQP($domainMessage);
        }

        // TODO: Logging published and not published.
    }

    /**
     * @param DomainMessage $domainMessage
     */
    private function publishWithAMQP(DomainMessage $domainMessage)
    {
        $key = null;

        $message = $this->createAMQPMessage($domainMessage);

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
            throw new \InvalidArgumentException(
                'Content type for class ' . $payloadClass . ' was already set to ' . $this->payloadClassToContentTypeMap[$payloadClass]
            );
        }
        $this->payloadClassToContentTypeMap[$payloadClass] = $contentType;
    }
}
