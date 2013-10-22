<?php

namespace PureMachine\Bundle\SDKBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use PureMachine\Bundle\SDKBundle\Store\Base\BaseStore;

/**
 * Event dispatched after the local or remote call
 * is executed
 */
class WebServiceCalledEvent extends Event
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
    private $outputData;

    /**
     * @var string
     */
    private $version;

    /**
     * @var boolean
     */
    private $local;

    /**
     * Class constructor
     *
     * @param string    $token
     * @param string    $webServiceName
     * @param BaseStore $outputData
     * @param string    $version
     * @param boolean   $local
     */
    public function __construct(
            $token,
            $webServiceName,
            BaseStore $outputData,
            $version,
            $local
            )
    {
        $this->token = $token;
        $this->webServiceName = $webServiceName;
        $this->outputData = $outputData;
        $this->version = $version;
        $this->local = $local;
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
     * Return outputData
     *
     * @return BaseStore
     */
    public function getOutputData()
    {
        return $this->outputData;
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
     * Returns a boolean indicator if the
     * service has been called remotely
     *
     * @return boolean
     */
    public function isRemote()
    {
        return !$this->local;
    }

    /**
     * Returns a boolean indicator if the
     * service has been called locally
     *
     * @return boolean
     */
    public function isLocal()
    {
        return $this->local;
    }

}
