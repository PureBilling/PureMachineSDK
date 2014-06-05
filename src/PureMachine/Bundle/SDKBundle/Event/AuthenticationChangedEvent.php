<?php

namespace PureMachine\Bundle\SDKBundle\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Event dispatched after the local or remote call
 * is executed
 */
class AuthenticationChangedEvent extends Event
{

    /**
     * @var string
     */
    private $login;

    /**
     * @var string
     */
    private $password;

    public function __construct(
        $login,
       $password
    )
    {
        $this->login = $login;
        $this->password = $password;
    }

    /**
     * Return token
     *
     * @return string
     */
    public function getLogin()
    {
        return $this->login;
    }

    /**
     * Return WebServiceName
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }
}
