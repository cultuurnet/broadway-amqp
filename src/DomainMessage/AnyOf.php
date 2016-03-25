<?php
/**
 * @file
 */

namespace CultuurNet\BroadwayAMQP\DomainMessage;

use Broadway\Domain\DomainMessage;
use CultuurNet\BroadwayAMQP\DomainMessage\SpecificationInterface;

class AnyOf implements SpecificationInterface
{
    /**
     * @var SpecificationCollection|SpecificationInterface[]
     */
    private $specifications;

    public function __construct(SpecificationCollection $specifications)
    {
        $this->specifications = $specifications;
    }

    /**
     * @inheritdoc
     */
    public function isSatisfiedBy(DomainMessage $domainMessage)
    {
        foreach ($this->specifications as $specification) {
            if ($specification->isSatisfiedBy($domainMessage)) {
                return true;
            }
        }

        return false;
    }
}
