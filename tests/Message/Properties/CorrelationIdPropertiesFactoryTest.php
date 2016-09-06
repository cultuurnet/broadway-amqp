<?php

namespace CultuurNet\BroadwayAMQP\Message\Properties;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;

class CorrelationIdPropertiesFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CorrelationIdPropertiesFactory
     */
    private $factory;

    public function setUp()
    {
        $this->factory = new CorrelationIdPropertiesFactory();
    }

    /**
     * @test
     */
    public function it_determines_correlation_id_based_on_message_id_and_playhead()
    {
        $id = 'effa2456-de78-480c-90ef-eb0a02b687c8';
        $playhead = 3;

        $domainMessage = new DomainMessage($id, $playhead, new Metadata(), new \stdClass(), DateTime::now());

        $expectedProperties = ['correlation_id' => 'effa2456-de78-480c-90ef-eb0a02b687c8-3'];
        $actualProperties = $this->factory->createProperties($domainMessage);

        $this->assertEquals($expectedProperties, $actualProperties);
    }
}
