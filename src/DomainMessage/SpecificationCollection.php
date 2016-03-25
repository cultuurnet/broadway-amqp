<?php
/**
 * @file
 */

namespace CultuurNet\BroadwayAMQP\DomainMessage;

use TwoDotsTwice\Collection\AbstractCollection;
use TwoDotsTwice\Collection\CollectionInterface;

class SpecificationCollection extends AbstractCollection
{
    protected function getValidObjectType()
    {
        return SpecificationInterface::class;
    }
}
