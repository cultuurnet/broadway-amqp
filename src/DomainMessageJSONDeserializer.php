<?php

namespace CultuurNet\BroadwayAMQP;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\Serializer\SerializableInterface;
use CultuurNet\Deserializer\JSONDeserializer;
use ValueObjects\StringLiteral\StringLiteral;

class DomainMessageJSONDeserializer extends JSONDeserializer
{
    /**
     * Fully qualified class name of the payload. This class should implement
     * Broadway\Serializer\SerializableInterface.
     *
     * @var $payloadClass
     */
    private $payloadClass;

    /**
     * @param string $payloadClass
     */
    public function __construct($payloadClass)
    {
        parent::__construct(true);
        
        if (!in_array(SerializableInterface::class, class_implements($payloadClass))) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Class \'%s\' does not implement ' . SerializableInterface::class,
                    $payloadClass
                )
            );
        }

        $this->payloadClass = $payloadClass;
    }

    /**
     * @inheritdoc
     */
    public function deserialize(StringLiteral $data)
    {
        $data = parent::deserialize(
            $data
        );

        $payloadClass = $this->payloadClass;

        return new DomainMessage(
            $data['id'],
            $data['playhead'],
            Metadata::deserialize($data['metadata']),
            $payloadClass::deserialize($data['payload']),
            DateTime::fromString($data['recorded_on'])
        );
    }
}
