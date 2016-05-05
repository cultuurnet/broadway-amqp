<?php

namespace CultuurNet\BroadwayAMQP\Normalizer;

use Broadway\Domain\DomainEventStream;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\Domain\DateTime as BroadwayDateTime;
use CultuurNet\BroadwayAMQP\Dummies\DummyEvent;

class CombinedDomainMessageNormalizerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CombinedDomainMessageNormalizer|\PHPUnit_Framework_MockObject_MockObject
     */
    private $combinedDomainMessageNormalizer;

    /**
     * @var domainMessageNormalizerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $domainMessageNormalizer;

    /**
     * @var DomainMessage
     */
    private $domainMessage;

    protected function setUp()
    {

        $this->combinedDomainMessageNormalizer = new CombinedDomainMessageNormalizer();

        $this->domainMessageNormalizer = $this->getMock(DomainMessageNormalizerInterface::class);

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
    public function it_returns_an_event_stream_when_no_normalizers_are_set()
    {

        $domainStream = $this->combinedDomainMessageNormalizer->normalize($this->domainMessage);

        $expectedDomainStream = new DomainEventStream(
            array($this->domainMessage)
        );

        $this->assertEquals($domainStream, $expectedDomainStream);
    }

    /**
     * @test
     */
    public function it_returns_an_event_stream_when_normalizers_are_set()
    {

        $expectedDomainStream = new DomainEventStream(
            array($this->domainMessage)
        );

        $this->domainMessageNormalizer->expects($this->once())
            ->method('getSupportedEvents')
            ->willReturn(array(DummyEvent::class));

        $this->domainMessageNormalizer->expects($this->once())
            ->method('normalize')
            ->with($this->domainMessage)
            ->willReturn($expectedDomainStream);

        $combinedDomainMessageNormalizerWithNormalizer = $this->combinedDomainMessageNormalizer->withNormalizer(
            $this->domainMessageNormalizer
        );

        $domainStream = $combinedDomainMessageNormalizerWithNormalizer->normalize($this->domainMessage);

        $this->assertEquals($domainStream, $expectedDomainStream);

    }

    /**
     * @test
     */
    public function it_returns_no_supported_types()
    {
        $this->assertEmpty($this->combinedDomainMessageNormalizer->getSupportedEvents());
    }
}
