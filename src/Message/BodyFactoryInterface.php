<?php
/**
 * @file
 */

namespace CultuurNet\BroadwayAMQP\Message;

use Broadway\Domain\DomainMessage;

interface BodyFactoryInterface
{
    /**
     * @param DomainMessage $domainMessage
     * @return string
     */
    public function createBody(DomainMessage $domainMessage);
}
