<?php

namespace CultuurNet\BroadwayAMQP\Normalizer;

use Broadway\Domain\DomainEventStream;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\Domain\DateTime as BroadwayDateTime;
use CultuurNet\BroadwayAMQP\Dummies\DummyEvent;
use CultuurNet\BroadwayAMQP\Dummies\DummyEventSubclass;

class CombinedDomainMessageNormalizerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CombinedDomainMessageNormalizer|\PHPUnit_Framework_MockObject_MockObject
     */
    private $combinedDomainMessageNormalizer;

    /**
     * @var DomainMessageNormalizerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $dummyNormalizer;

    /**
     * @var DomainMessageNormalizerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $dummyChildNormalizer;

    /**
     * @var DomainMessage
     */
    private $dummyDomainMessage;

    protected function setUp()
    {
        $this->dummyNormalizer = $this->getMock(DomainMessageNormalizerInterface::class);
        $this->dummyNormalizer->expects($this->any())
            ->method('getSupportedEvents')
            ->willReturn(array(DummyEvent::class));

        $this->dummyChildNormalizer = $this->getMock(DomainMessageNormalizerInterface::class);
        $this->dummyChildNormalizer->expects($this->any())
            ->method('getSupportedEvents')
            ->willReturn(array(DummyEventSubclass::class));

        $this->combinedDomainMessageNormalizer = (new CombinedDomainMessageNormalizer())
            ->withNormalizer($this->dummyNormalizer)
            ->withNormalizer($this->dummyChildNormalizer);

        $this->dummyDomainMessage = new DomainMessage(
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
    public function it_returns_an_event_stream_when_no_normalizers_are_set()
    {
        $normalizer = new CombinedDomainMessageNormalizer();
        $domainStream = $normalizer->normalize($this->dummyDomainMessage);

        $expectedDomainStream = new DomainEventStream(
            array($this->dummyDomainMessage)
        );

        $this->assertEquals($domainStream, $expectedDomainStream);
    }

    /**
     * @test
     */
    public function it_returns_a_normalized_event_stream_when_normalizers_are_set()
    {
        $normalizedDomainMessage = new DomainMessage(
            'F68E71A1-DBB0-4542-AEE5-BD937E095F74',
            2,
            new Metadata(),
            new DummyEventSubclass(
                'F68E71A1-DBB0-4542-AEE5-BD937E095F74',
                'test 123 456'
            ),
            BroadwayDateTime::fromString('2015-01-02T08:40:00+0100')
        );

        $expectedDomainEventStream = new DomainEventStream(
            array($normalizedDomainMessage)
        );

        $this->dummyNormalizer->expects($this->once())
            ->method('normalize')
            ->with($this->dummyDomainMessage)
            ->willReturn($expectedDomainEventStream);

        $actualEventDomainStream = $this->combinedDomainMessageNormalizer->normalize($this->dummyDomainMessage);

        $this->assertEquals($expectedDomainEventStream, $actualEventDomainStream);
    }

    /**
     * @test
     */
    public function it_returns_the_supported_types_of_its_injected_normalizers()
    {
        $this->assertEquals(
            [
                DummyEvent::class,
                DummyEventSubclass::class,
            ],
            $this->combinedDomainMessageNormalizer->getSupportedEvents()
        );
    }
}
