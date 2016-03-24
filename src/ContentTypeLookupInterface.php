<?php

namespace CultuurNet\BroadwayAMQP;

interface ContentTypeLookupInterface
{
    /**
     * @param $payloadClass
     * @param $contentType
     * @return static
     */
    public function withContentType($payloadClass, $contentType);

    /**
     * @param $payloadClass
     * @return string
     */
    public function getContentType($payloadClass);
}
