<?php
/**
 * @file
 */

namespace CultuurNet\BroadwayAMQP\Message;

use Broadway\Domain\DomainMessage;
use Broadway\Serializer\SerializableInterface;
use Broadway\Serializer\SerializationException;
use Broadway\Serializer\SerializerInterface;
use stdClass;

class PayloadOnlyBodyFactory implements BodyFactoryInterface
{
    /**
     * @inheritdoc
     */
    public function createBody(DomainMessage $domainMessage)
    {
        $this->guardSerializable($domainMessage->getPayload());

        return json_encode(
            $domainMessage->getPayload()->serialize()
        );
    }

    /**
     * @param mixed $object
     * @throws SerializationException
     */
    private function guardSerializable($object)
    {
        if (!$object instanceof SerializableInterface) {
            throw new SerializationException(
                'Unable to serialize ' . get_class($object)
            );
        }
    }
}
