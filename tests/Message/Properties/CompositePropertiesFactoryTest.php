<?php

namespace CultuurNet\BroadwayAMQP\Message\Properties;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;

class CompositePropertiesFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PropertiesFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $mockFactory1;

    /**
     * @var PropertiesFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $mockFactory2;

    /**
     * @var CompositePropertiesFactory
     */
    private $compositeFactory;

    public function setUp()
    {
        $this->mockFactory1 = $this->getMock(PropertiesFactoryInterface::class);
        $this->mockFactory2 = $this->getMock(PropertiesFactoryInterface::class);

        $this->compositeFactory = (new CompositePropertiesFactory())
            ->with($this->mockFactory1)
            ->with($this->mockFactory2);
    }

    /**
     * @test
     */
    public function it_combines_properties_from_all_injected_property_factories()
    {
        $domainMessage = new DomainMessage(
            '7a8ccbc5-d802-46c8-b9ec-7a286bc7653b',
            0,
            new Metadata(),
            new \stdClass(),
            DateTime::now()
        );

        $this->mockFactory1->expects($this->once())
            ->method('createProperties')
            ->with($domainMessage)
            ->willReturn(
                [
                    'correlation_id' => '123456',
                    'content_type' => 'text/plain',
                ]
            );

        $this->mockFactory2->expects($this->once())
            ->method('createProperties')
            ->with($domainMessage)
            ->willReturn(
                [
                    'content_type' => 'application/json+ld',
                    'delivery_mode' => 2,
                ]
            );

        $expectedProperties = [
            'correlation_id' => '123456',
            'content_type' => 'application/json+ld',
            'delivery_mode' => 2,
        ];

        $actualProperties = $this->compositeFactory->createProperties($domainMessage);

        $this->assertEquals($expectedProperties, $actualProperties);
    }
}
