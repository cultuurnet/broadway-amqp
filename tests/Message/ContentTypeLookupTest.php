<?php

namespace CultuurNet\BroadwayAMQP\Message;

use CultuurNet\BroadwayAMQP\Dummies\DummyEvent;

class ContentTypeLookupTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ContentTypeLookup
     */
    protected $contentTypeLookup;

    public function setUp()
    {
        $this->contentTypeLookup = new ContentTypeLookup();
    }

    /**
     * @test
     */
    public function it_can_return_the_content_type_when_added_to_the_mapping()
    {
        $this->contentTypeLookup = $this->contentTypeLookup->withContentType(
            DummyEvent::class,
            'application/vnd.cultuurnet.udb3-events.dummy-event+json'
        );

        $expectedContentType = 'application/vnd.cultuurnet.udb3-events.dummy-event+json';
        $contentType = $this->contentTypeLookup->getContentType(DummyEvent::class);

        $this->assertEquals($expectedContentType, $contentType);
    }

    /**
     * @test
     */
    public function it_throws_invalid_argument_exception_when_payload_class_is_not_a_string()
    {
        $this->setExpectedException(
            \InvalidArgumentException::class,
            'Value for argument payloadClass should be a string'
        );

        $this->contentTypeLookup->withContentType(
            1,
            'application/vnd.cultuurnet.udb3-events.dummy-event+json'
        );
    }

    /**
     * @test
     */
    public function it_throws_invalid_argument_exception_when_content_type_is_not_a_string()
    {
        $this->setExpectedException(
            \InvalidArgumentException::class,
            'Value for argument contentType should be a string'
        );

        $this->contentTypeLookup->withContentType(
            DummyEvent::class,
            1
        );
    }

    /**
     * @test
     */
    public function it_throws_runtime_exception_when_setting_the_same_content_type()
    {
        $contentTypeLookup = $this->contentTypeLookup->withContentType(
            DummyEvent::class,
            'application/vnd.cultuurnet.udb3-events.dummy-event+json'
        );

        $this->setExpectedException(
            \InvalidArgumentException::class
        );

        $contentTypeLookup->withContentType(
            DummyEvent::class,
            'application/vnd.cultuurnet.udb3-events.dummy-event+json'
        );
    }

    /**
     * @test
     */
    public function it_throws_a_runtime_exception_when_the_content_type_cannot_be_found()
    {
        $this->setExpectedException(
            \RuntimeException::class,
            'Unable to find the content type of CultuurNet\BroadwayAMQP\Dummies\DummyEvent'
        );

        $payloadClass = 'CultuurNet\BroadwayAMQP\Dummies\DummyEvent';

        $this->contentTypeLookup->getContentType($payloadClass);
    }
}
