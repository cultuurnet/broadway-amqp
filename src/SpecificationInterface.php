<?php

namespace CultuurNet\BroadwayAMQP;

use Broadway\Domain\DomainMessage;

interface SpecificationInterface
{
    public function isSatisfiedBy(DomainMessage $domainMessage);
}
