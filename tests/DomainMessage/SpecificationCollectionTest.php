<?php

namespace CultuurNet\BroadwayAMQP\DomainMessage;

class SpecificationCollectionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function it_does_accept_objects_of_type_specification_class()
    {
        $specification = $this->getMock(SpecificationInterface::class);

        $specifications = new SpecificationCollection();
        $specifications = $specifications->with($specification);

        $this->assertTrue($specifications->contains($specification));
    }

    /**
     * @test
     */
    public function it_does_accept_objects_of_subclass_type_specification()
    {
        $payloadSpecification = $this->getMock(
            PayloadIsInstanceOf::class,
            array(),
            array('typeName')
        );

        $specifications = new SpecificationCollection();
        $specifications = $specifications->with($payloadSpecification);

        $this->assertTrue($specifications->contains($payloadSpecification));
    }

    /**
     * @test
     */
    public function it_does_throws_invalid_argument_exception_for_wrong_types()
    {
        $wrongSpecification = $this->getMock(\JsonSerializable::class);

        $message = sprintf(
            'Expected instance of %s, found %s instead.',
            SpecificationInterface::class,
            get_class($wrongSpecification)
        );

        $this->setExpectedException(
            \InvalidArgumentException::class,
            $message
        );

        $specifications = new SpecificationCollection();
        $specifications->with($wrongSpecification);
    }
}
