<?php

namespace CultuurNet\BroadwayAMQP\Normalizer;

use Broadway\Domain\DomainEventStreamInterface;
use Broadway\Domain\DomainMessage;

interface DomainMessageNormalizerInterface
{

    /**
     * @return array
     */
    public function getSupportedEvents();

    /**
     * @param DomainMessage $domainMessage
     * @return DomainEventStreamInterface
     */
    public function normalize(DomainMessage $domainMessage);
}
