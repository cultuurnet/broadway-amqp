<?php

namespace CultuurNet\BroadwayAMQP\Normalizer;

use Broadway\Domain\DomainEventStream;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\Domain\DateTime as BroadwayDateTime;
use CultuurNet\BroadwayAMQP\Dummies\DummyEvent;
use Broadway\EventHandling\EventListenerInterface as AMQPPublisherInterface;

/**
 * Class DomainMessageNormalizerDecoratorTest
 * @package CultuurNet\BroadwayAMQP\Normalizer
 */
class DomainMessageNormalizerAMQPPublisherDecoratorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DomainMessageNormalizerAMQPPublisherDecorator|\PHPUnit_Framework_MockObject_MockObject
     */
    private $domainMessageNormalizerAMQPPublisherDecorator;
    
    /**
     * @var AMQPPublisherInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $amqpPublisher;
    
    /**
     * @var DomainMessageNormalizerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $domainMessageNormalizer;

    /**
     * @var DomainMessage
     */
    private $domainMessage;

    protected function setUp()
    {
        $this->amqpPublisher = $this->getMock(AMQPPublisherInterface::class);

        $this->domainMessageNormalizer = $this->getMock(
            DomainMessageNormalizerInterface::class
        );

        $this->domainMessageNormalizerAMQPPublisherDecorator = new DomainMessageNormalizerAMQPPublisherDecorator(
            $this->amqpPublisher,
            $this->domainMessageNormalizer
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
    public function it_does_call_the_normalize_method()
    {
        $this->domainMessageNormalizer->expects($this->once())
             ->method('normalize')
             ->will($this->returnValue(new DomainEventStream([$this->domainMessage])));

        $this->domainMessageNormalizerAMQPPublisherDecorator->handle($this->domainMessage);
    }
    
    /**
     * @test
     */
    public function it_does_call_the_handle_method_of_AMQPPublisher()
    {
        $this->domainMessageNormalizer->expects($this->once())
            ->method('normalize')
            ->will($this->returnValue(
                new DomainEventStream([
                    $this->domainMessage,
                    $this->domainMessage
                ])
            ));

        $this->amqpPublisher->expects($this->exactly(2))->method('handle');

        $this->domainMessageNormalizerAMQPPublisherDecorator->handle($this->domainMessage);
    }
}
