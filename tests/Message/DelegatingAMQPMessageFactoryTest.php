<?php

namespace CultuurNet\BroadwayAMQP\Message;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use CultuurNet\BroadwayAMQP\Dummies\DummyEvent;
use PhpAmqpLib\Message\AMQPMessage;

class DelegatingAMQPMessageFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var BodyFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $bodyFactory;

    /**
     * @var PropertiesFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $propertiesFactory;

    /**
     * @var DelegatingAMQPMessageFactory
     */
    private $messageFactory;

    public function setUp()
    {
        $this->bodyFactory = $this->getMock(BodyFactoryInterface::class);
        $this->propertiesFactory = $this->getMock(PropertiesFactoryInterface::class);

        $this->messageFactory = new DelegatingAMQPMessageFactory(
            $this->bodyFactory,
            $this->propertiesFactory
        );
    }

    /**
     * @test
     */
    public function it_delegates_body_and_properties_creation_to_the_respective_injected_factories()
    {
        $domainMessage = new DomainMessage(
            '06d0906d-e235-40d2-b9f3-1fa6aebc9e00',
            1,
            new Metadata(),
            new DummyEvent('06d0906d-e235-40d2-b9f3-1fa6aebc9e00', 'foo'),
            DateTime::now()
        );

        $body = '{"foo":"bar"}';
        $properties = ['deliver_mode' => 2];

        $expectedAMQPMessage = new AMQPMessage($body, $properties);

        $this->bodyFactory->expects($this->once())
            ->method('createBody')
            ->with($domainMessage)
            ->willReturn($body);

        $this->propertiesFactory->expects($this->once())
            ->method('createProperties')
            ->with($domainMessage)
            ->willReturn($properties);

        $actualAMQPMessage = $this->messageFactory->createAMQPMessage($domainMessage);

        $this->assertEquals($expectedAMQPMessage, $actualAMQPMessage);
    }
}
