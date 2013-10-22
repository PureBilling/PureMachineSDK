<?php

namespace PureMachine\Bundle\SDKBundle\Service;

use Doctrine\DBAL\Logging\DebugStack;

class WebServiceClientDebug extends WebServiceClient
{
    private $stopWatchEvent;
    private $doctrineDebugStack = array();

    public function call($webServiceName, $inputData=null,
                              $version='V1')
    {
        $doctrineDebugStack = new DebugStack();
        $this->getContainer()
            ->get('doctrine')
            ->getConnection()
            ->getConfiguration()
            ->setSQLLogger($doctrineDebugStack);

        $stopwatch = $this->getContainer()->get('debug.stopwatch');
        $stopwatch->start($webServiceName, 'webService');
        $answer = parent::call($webServiceName, $inputData,$version);
        $event = $stopwatch->stop($webServiceName);
        $this->stopWatchEvent = $event;

        $this->doctrineDebugStack = $doctrineDebugStack;

        return $answer;
    }

    public function getLastStopWatchEvent()
    {
        return $this->stopWatchEvent;
    }

    public function getDoctrineDebugStack()
    {
        return $this->doctrineDebugStack;
    }

}
