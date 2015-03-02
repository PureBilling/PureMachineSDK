<?php

namespace PureMachine\Bundle\SDKBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use PureMachine\Bundle\SDKBundle\Store\Base\BaseStore;

/**
 * Event dispatched before the local or remote call
 * is executed
 */
class WebServiceCallingEvent extends Event
{

    /**
     * @var string
     */
    private $token;

    /**
     * @var string
     */
    private $webServiceName;

    /**
     * @var BaseStore
     */
    private $inputData;

    /**
     * @var string
     */
    private $version;

    /**
     * @var integer
     */
    private $connectionTimeout;

    /**
     * @var integer
     */
    private $timeout;

    /**
     * @var string
     */
    private $proxy;

    /**
     * @var integer
     */
    private $proxyPort;

    /**
     * Class constructor
     *
     * @param string    $token
     * @param string    $webServiceName
     * @param BaseStore $inputData
     * @param string    $version
     */
    public function __construct(
            $token,
            $webServiceName,
            BaseStore $inputData,
            $version,
            $connectionTimeout = null,
            $timeout = null,
            $proxy = null,
            $proxyHost = null
            )
    {
        $this->token = $token;
        $this->webServiceName = $webServiceName;
        $this->inputData = $inputData;
        $this->version = $version;
        $this->connectionTimeout = $connectionTimeout;
        $this->timeout = $timeout;
        $this->proxy = $proxy;
        $this->proxyPort = $proxyHost;
    }

    /**
     * Return token
     *
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * Return WebServiceName
     *
     * @return string
     */
    public function getWebServiceName()
    {
        return $this->webServiceName;
    }

    /**
     * Return inputData
     *
     * @return BaseStore
     */
    public function getInputData()
    {
        return $this->inputData;
    }

    /**
     * Return version
     *
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Return version
     *
     * @return inteter
     */
    public function getConnectionTimeout()
    {
        return $this->connectionTimeout;
    }

    /**
     * Return version
     *
     * @return integer
     */
    public function getTimeout()
    {
        return $this->timeout;
    }

    /**
     * Return version
     *
     * @return string
     */
    public function getProxy()
    {
        return $this->proxy;
    }

    /**
     * Return version
     *
     * @return string
     */
    public function getProxyPort()
    {
        return $this->proxyPort;
    }

}
