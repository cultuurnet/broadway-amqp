<?php

namespace CultuurNet\BroadwayAMQP\Dummies;

use Broadway\Domain\DomainMessage;

class DummyNeverSatisfied implements SpecificationInterface
{
    public function isSatisfiedBy(DomainMessage $domainMessage)
    {
        return false;
    }
}