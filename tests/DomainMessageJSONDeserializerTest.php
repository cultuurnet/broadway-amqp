<?php

namespace CultuurNet\BroadwayAMQP;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use CultuurNet\BroadwayAMQP\Dummies\DummyEvent;
use CultuurNet\BroadwayAMQP\Dummies\DummyEventNotSerializable;
use PHPUnit\Framework\TestCase;
use ValueObjects\StringLiteral\StringLiteral;

class DomainMessageJSONDeserializerTest extends TestCase
{
    /**
     * @var DomainMessageJSONDeserializer
     */
    protected $domainMessageJSONDeserializer;

    public function setUp()
    {
        $this->domainMessageJSONDeserializer = new DomainMessageJSONDeserializer(
            DummyEvent::class
        );
    }

    /**
     * @test
     */
    public function it_throws_an_error_if_payloadclass_does_not_implement_SerializableInterface()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Class \'CultuurNet\BroadwayAMQP\Dummies\DummyEventNotSerializable\' does not implement ' .
            'Broadway\Serializer\SerializableInterface'
        );

        new DomainMessageJSONDeserializer(DummyEventNotSerializable::class);
    }

    /**
     * @test
     */
    public function it_can_deserialize_a_domain_message()
    {
        $jsonData = new StringLiteral(
            file_get_contents(__DIR__ . '/Dummies/domain-message-dummy-event.json')
        );

        $expectedDomainMessage = new DomainMessage(
            'message-id-123',
            0,
            new Metadata(),
            new DummyEvent('foo', 'bla'),
            DateTime::fromString('2016-03-25')
        );

        $domainMessage = $this->domainMessageJSONDeserializer->deserialize($jsonData);

        $this->assertEquals($expectedDomainMessage, $domainMessage);
    }
}
