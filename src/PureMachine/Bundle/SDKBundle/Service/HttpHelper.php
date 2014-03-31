<?php

namespace PureMachine\Bundle\SDKBundle\Service;

use PureMachine\Bundle\SDKBundle\Store\LogStore;
use PureMachine\Bundle\SDKBundle\Exception\HTTPException;
use PureMachine\Bundle\SDKBundle\Event\HttpRequestEvent;

class HttpHelper
{
    private $log= null;
    private $symfonyContainer = null;
    private $metadata = array();

    public function __construct($logActivity=false)
    {
        if ($logActivity) $this->resetLog();
    }

    public function setContainer($container)
    {
        $this->symfonyContainer = $container;
    }

    public function resetLog()
    {
        $this->log = new LogStore();
    }

    public function getLog()
    {
        return $this->log;
    }

    public function getFullUrl($url, $data)
    {
        $urlParameters = "json=" . json_encode($data);

        return "$url?$urlParameters";
    }

    public function setNextRequestEventMetadata($metadata)
    {
        $this->metadata = $metadata;
    }

    public function getJsonResponse($url, $data=array(), $method='POST',
                                 $headers=array(), $authenticationToken=null)
    {
        $output = $this->getResponse($url, $data, $method, $headers, $authenticationToken);
        $json = json_decode($output);

        if ($json == null) {
            $getUrl = $this->getFullUrl($url, $data);
            $errorMessage = "can't decode JSON output";
            $e = new HTTPException($errorMessage);
            $e->addMessage('json decoder error', json_last_error());
            $e->addMessage('Ouptut', $output);
            $e->addMessage('called URL', $url);
            $e->addMessage('debug URL (rebuilded)', $getUrl);
            $e->addMessage('data sent:', $data);
            throw $e;
        }

        return $json;
    }

    public function getResponse($url, $data=array(), $method='POST',
                                $headers=array(), $authenticationToken=null)
    {
        $data2 = array('json' => json_encode($data));

        return $this->httpRequest($url, $data2, $method, $headers, $authenticationToken);
    }

    public function httpRequest($url, $data=array(), $method='POST',
                                $headers=array(), $authenticationToken=null)
    {
        $log = $this->log;
        $ch = curl_init();

        if ($method == 'GET') {
                $url = $this->addGetParametersToUrl($url, $data);
        } elseif ($method == 'POST') {
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        }

        if ($log) $log->getTitle("call: $url");

        if ($log) {
            $log->addMessage('method', $method);
            $log->addMessage('called URL', $url);
            $log->addMessage("$method values", json_encode($data));
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_USERAGENT, 'PureMachine HttpHelpers:getJsonAnswer');

        if ($authenticationToken) {
            curl_setopt($ch, CURLOPT_USERPWD, $authenticationToken);
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        }

        $output = curl_exec($ch);
        $info = curl_getinfo($ch);
        $curlError = curl_error($ch);
        $curlErrorNo = curl_errno($ch);
        curl_close($ch);
        $statusCode = $info['http_code'];

        if ($statusCode == 0) {
            $message = "CURL error: $statusCode ($curlErrorNo:$curlError)";
            $e = new HTTPException($message);
            $e->addMessage('Ouptut', $output);
            $e->addMessage('called URL', $url);
            $e->addMessage('data sent:', $data);
            $this->triggerHttpRequestEvent($data, $output, $url, $method, $statusCode);
            throw $e;
        }

        if ($statusCode == 404) {
            $e = new HTTPException("HTTP error :" . $statusCode ." for ". $url
                                  ." . Page or service not found.",
                                  HTTPException::HTTP_404);
            $e->addMessage('Ouptut', $output);
            $e->addMessage('called URL', $url);
            $e->addMessage('data sent:', $data);
            $this->triggerHttpRequestEvent($data, $output, $url, $method, $statusCode);
            throw $e;
        }

        if ($statusCode == 401) {
            $e = new HTTPException("HTTP error :" . $statusCode ." for ". $url
                                  ." . Invalid credentials.",
                                   HTTPException::HTTP_401);
            $e->addMessage('Ouptut', $output);
            $e->addMessage('called URL', $url);
            $e->addMessage('data sent:', $data);
            $this->triggerHttpRequestEvent($data, $output, $url, $method, $statusCode);
            throw $e;
        }

        if ($statusCode != 200) {
            $errorMessage = "HTTP error :" . $statusCode . " for $url";
            $e = new HTTPException($errorMessage);
            $e->addMessage('Ouptut', $output);
            $e->addMessage('called URL', $url);
            $e->addMessage('data sent:', $data);
            $this->triggerHttpRequestEvent($data, $output, $url, $method, $statusCode);
            throw $e;
        }

        $this->triggerHttpRequestEvent($data, $output, $url, $method, $statusCode);

        return $output;
    }

    private function triggerHttpRequestEvent($inputData, $outputData, $originalUrl, $method, $code)
    {
        /**
         * Event can be desactived by Metadatas
         */
        if (array_key_exists('disableEvent', $this->metadata) && $this->metadata['disableEvent'] == true) {
            $disable = true;
        } else {
            $disable = false;
        }

        if ($this->symfonyContainer && !$disable) {
            $event = new HttpRequestEvent($inputData, $outputData, $originalUrl, $method, $code, $this->metadata);
            $eventDispatcher = $this->symfonyContainer->get("event_dispatcher");
            $eventDispatcher->dispatch("puremachine.httphelper.request", $event);
        }

        //Remove metadatas
        $this->metadata = array();
    }

    public function addGetParametersToUrl($url, $parameters)
    {
        $frag = parse_url($url);
        $queryStringArray = array();

        if (isset($frag['query'])) {
            parse_str($frag['query'], $queryStringArray);
        }

        $queryString = http_build_query(array_merge($queryStringArray, $parameters));

        if (!array_key_exists('path', $frag)) {
            $frag['path'] = "";
        }

        if (!array_key_exists('port', $frag)) {
            return $frag['scheme']. '://' . $frag['host']. $frag['path'] ."?" . $queryString;
        }

        return $frag['scheme']. '://' . $frag['host'].":". $frag['port'] . $frag['path'] ."?" . $queryString;
    }
}
