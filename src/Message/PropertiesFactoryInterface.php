<?php

namespace CultuurNet\BroadwayAMQP\Message;

use Broadway\Domain\DomainMessage;

interface PropertiesFactoryInterface
{
    /**
     * @param DomainMessage $domainMessage
     * @return array
     */
    public function createProperties(DomainMessage $domainMessage);
}
