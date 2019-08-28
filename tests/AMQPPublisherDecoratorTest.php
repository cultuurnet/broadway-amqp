<?php

namespace CultuurNet\BroadwayAMQP;

use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\Domain\DateTime as BroadwayDateTime;
use CultuurNet\BroadwayAMQP\Dummies\DummyEvent;
use Broadway\EventHandling\EventListenerInterface as AMQPPublisherInterface;
use PHPUnit\Framework\TestCase;

class AMQPPublisherDecoratorTest extends TestCase
{
    /**
     * @var AMQPPublisherInterface|\PHPUnit\Framework\MockObject\
     */
    private $amqpPublisher;

    /**
     * @var AMQPPublisherDecorator
     */
    private $amqpPublisherDecorator;

    /**
     * @var DomainMessage
     */
    private $domainMessage;
    
    
    protected function setUp()
    {
        $this->amqpPublisher = $this->createMock(AMQPPublisherInterface::class);

        $this->amqpPublisherDecorator = $this->getMockForAbstractClass(
            AMQPPublisherDecorator::class,
            [$this->amqpPublisher]
        );

        $this->domainMessage = new DomainMessage(
            'F68E71A1-DBB0-4542-AEE5-BD937E095F74',
            2,
            new Metadata(),
            new DummyEvent(
                'F68E71A1-DBB0-4542-AEE5-BD937E095F74',
                'test 123 456'
            ),
            BroadwayDateTime::fromString('2015-01-02T08:40:00+0100')
        );
    }

    /**
     * @test
     */
    public function it_calls_handle_method_from_injected_interface()
    {
        $this->amqpPublisher->expects($this->once())->method('handle');

        $this->amqpPublisherDecorator->handle($this->domainMessage);
    }
}
