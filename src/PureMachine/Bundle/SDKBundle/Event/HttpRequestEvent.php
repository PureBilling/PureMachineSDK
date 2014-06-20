<?php

namespace PureMachine\Bundle\SDKBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use PureMachine\Bundle\SDKBundle\Store\Base\BaseStore;

/**
 * Event dispatched after the local or remote call
 * is executed
 */
class HttpRequestEvent extends Event
{
    /**
     * @var BaseStore
     */
    protected $inputData;

    /**
     * @var BaseStore
     */
    protected $outputData;

    /**
     * @var string
     */
    protected $originalUrl;

    /**
     * @var string
     */
    protected $method;

    /**
     * @var integer
     */
    protected $httpAnswerCode;

    /**
     * @var array
     */
    protected $metadata;

    public function __construct(
        $inputData,
        $outputData,
        $originalUrl,
        $method,
        $httpAnswerCode,
        $metadata = array()
    )
    {
        $this->inputData = $inputData;
        $this->outputData = $outputData;
        $this->originalUrl = $originalUrl;
        $this->method = $method;
        $this->httpAnswerCode = $httpAnswerCode;
        $this->metadata = $metadata;
    }

    /**
     * Return outputData
     *
     * @return BaseStore
     */
    public function getInputData()
    {
        return $this->inputData;
    }

    /**
     * Return outputData
     *
     * @return BaseStore
     */
    public function getOutputData()
    {
        return $this->outputData;
    }

    /**
     * Return outputData
     *
     * @return BaseStore
     */
    public function setOutputData($outputData)
    {
        $this->outputData = $outputData;
    }

    /**
     * @return string
     */
    public function getOriginalUrl()
    {
        return $this->originalUrl;
    }

    /**
     * HTTP method used : GET or POST
     *
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @return integer
     */
    public function getHttpAnswerCode()
    {
        return $this->httpAnswerCode;
    }

    public function getMetadata($key=null)
    {
        if (!$key) return $this->metadata;
        if (array_key_exists($key, $this->metadata)) {
            return $this->metadata[$key];
        }

        return null;
    }

    public function setHttpAnswerCode($code)
    {
        $this->httpAnswerCode = $code;
    }

    public function setMetadata($meta)
    {
        $this->metadata = $meta;
    }

    public function setMetadataValue($key, $value)
    {
        $this->metadata[$key] = $value;
    }
}
