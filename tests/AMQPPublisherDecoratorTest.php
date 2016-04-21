<?php

namespace CultuurNet\BroadwayAMQP;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\Domain\DateTime as BroadwayDateTime;
use CultuurNet\BroadwayAMQP\Dummies\DummyEvent;
use Broadway\EventHandling\EventListenerInterface as AMQPPublisherInterface;

class AMQPPublisherDecoratorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AMQPPublisherInterface
     */
    private $amqpPublisher;
    
    /**
     * @var DomainMessage
     */
    private $domainMessage;

    /**
     * @var AMQPPublisherDecorator
     */
    private $amqpPublisherDecorator;
    
    
    protected function setUp()
    {
        //$this->amqpPublisherDecorator = $this->getMock(AMQPPublisherDecorator::class, ['handle'], [], 'AMQPPublisherDecorator', false);
        $this->amqpPublisherDecorator = $this
            ->getMockBuilder(AMQPPublisherDecorator::class)
            ->setMethods(null)
            ->getMockForAbstractClass();
            //->disableOriginalConstructor()
        // TODO: still gives error: Argument 1 passed to CultuurNet\BroadwayAMQP\AMQPPublisherDecorator::__construct() must implement interface Broadway\EventHandling\EventListenerInterface, null given
        $this->amqpPublisher = $this->getMockBuilder(AMQPPublisherInterface::class)->getMock();
        //$this->amqpPublisherDecorator->amqpPublisher = $this->amqpPublisher; // nope :(
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
    public function it_should_call_the_handle_method_of_AMQPPublisherInterface()
    {
        $this->amqpPublisher->expects($this->once())->method('handle');
        $this->amqpPublisherDecorator->handle($this->domainMessage);
    }
}
