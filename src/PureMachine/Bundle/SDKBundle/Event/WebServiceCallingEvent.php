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
            $version
            )
    {
        $this->token = $token;
        $this->webServiceName = $webServiceName;
        $this->inputData = $inputData;
        $this->version = $version;
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

}
