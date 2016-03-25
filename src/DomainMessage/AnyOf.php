<?php
/**
 * @file
 */

namespace CultuurNet\BroadwayAMQP\DomainMessage;

use Broadway\Domain\DomainMessage;
use InvalidArgumentException;

class AnyOf implements SpecificationInterface
{
    /**
     * @var SpecificationInterface[]
     */
    private $specifications;

    public function __construct()
    {
        $specifications = func_get_args();

        foreach ($specifications as $specification) {
            if (!$specification instanceof SpecificationInterface) {
                throw new InvalidArgumentException('Argument should implement '  . SpecificationInterface::class);
            }
        }

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
