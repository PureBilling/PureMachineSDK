<?php

namespace PureMachine\Bundle\SDKBundle\Service;

use PureMachine\Bundle\SDKBundle\Exception\HTTPException;
use PureMachine\Bundle\SDKBundle\Store\LogStore;
use PureMachine\Bundle\SDKBundle\Event\HttpRequestEvent;

class HttpHelper
{
    private $log= null;
    private $symfonyContainer = null;
    private $metadata = array();
    private $lastAnswerHeaders = null;

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

    public function getSoapResponse($wsdl, $function, $data, $cookie=null)
    {
        $options = array();
        $start = microtime(true);
        $debug = false;
        $this->lastAnswerHeaders = null;
        $options['trace'] = 1;

        $client = new \SoapClient($wsdl, $options);

        if (is_array($cookie)) {
            foreach ($cookie as $key => $value) {
                $client->__setCookie($key, $value);
            }
        }

        try {
            $json = $client->__soapCall($function, $data);
        } catch (\Exception $e) {
            $duration = microtime(true) - $start;
            $this->triggerHttpRequestEvent($data, $e->getMessage(), $wsdl, 'SOAP', 500, $duration);
            throw $e;
        }

        $duration = microtime(true) - $start;
        $this->triggerHttpRequestEvent($data, json_encode($json), $wsdl, 'SOAP', 200, $duration);

        if ($debug) {
            echo "/****** calling $function on $wsdl *******/\n\n";
            echo "REQUEST:\n" . $client->__getLastRequest() . "\n\n";
            echo "ANSWER:\n" . $client->__getLastResponse() . "\n\n";
        }

        try {
            $this->lastAnswerHeaders = $this->http_parse_headers($client->__getLastResponseHeaders());
        } catch (\Exception $e) {}

        return $json;
    }

    public function getJsonResponse($url, $data=array(), $method='POST',
                                 $headers=array(), $authenticationToken=null)
    {
        $output = $this->getResponse($url, $data, $method, $headers, $authenticationToken);
        $json = json_decode($output);

        if ($json == null) {
            $getUrl = $this->getFullUrl($url, $data);
            $errorMessage = "can't decode JSON output";
            $e = $this->createException($errorMessage);
            $e->addMessage('json decoder error', json_last_error());
            $e->addMessage('output', $output);
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

    public function httpRequest($url, $data=null, $method='POST',
                                $headers=array(), $authenticationToken=null)
    {
        $log = $this->log;
        $ch = curl_init();
        $lastAnswerHeaders = null;
        $start = microtime(true);

        if (!$data) {
            $data = array();
        }

        if ($method == 'GET') {
                $url = $this->addGetParametersToUrl($url, $data);
        } elseif ($method == 'POST') {
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);

            if (is_array($data)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            } else {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            }
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
        $duration = microtime(true) - $start;

        if ($statusCode == 0) {

            switch($curlErrorNo) {
                case 7:
                    $exception_code = HTTPException::HTTP_002;
                    break;
                default:
                    $exception_code = HTTPException::HTTP_001;
            }

            $message = "CURL error: $statusCode ($curlErrorNo:$curlError)";
            $e = $this->createException($message, $exception_code);
            $e->addMessage('output', $output);
            $e->addMessage('called URL', $url);
            $e->addMessage('data sent:', $data);
            $this->triggerHttpRequestEvent($data, $output, $url, $method, $statusCode, $duration);
            throw $e;
        }

        if ($statusCode == 404) {
            $e = $this->createException("HTTP exception: error " . $statusCode ." for ". $url
                                  ." . Page or service not found.",
                                  HTTPException::HTTP_404);
            $e->addMessage('output', $output);
            $e->addMessage('called URL', $url);
            $e->addMessage('data sent:', $data);
            $this->triggerHttpRequestEvent($data, $output, $url, $method, $statusCode, $duration);
            throw $e;
        }

        if ($statusCode == 401) {
            $e = $this->createException("HTTP exception: error " . $statusCode ." for ". $url
                                  ." . Invalid credentials.",
                                   HTTPException::HTTP_401);
            $e->addMessage('output', $output);
            $e->addMessage('called URL', $url);
            $e->addMessage('data sent:', $data);
            $this->triggerHttpRequestEvent($data, $output, $url, $method, $statusCode, $duration);
            throw $e;
        }

        if ($statusCode != 200) {
            $errorMessage = "HTTP exception: error " . $statusCode . " for $url";
            $e = $this->createException($errorMessage, HTTPException::HTTP_500);
            $e->addMessage('output', $output);
            $e->addMessage('called URL', $url);
            $e->addMessage('data sent:', $data);
            $this->triggerHttpRequestEvent($data, $output, $url, $method, $statusCode, $duration);
            throw $e;
        }

        $this->triggerHttpRequestEvent($data, $output, $url, $method, $statusCode, $duration);

        return $output;
    }

    public function disableNextRequestEvent($disableAlsoInDev=false)
    {
        /**
         * In dev mode, we never disable events.
         */
        if ($this->symfonyContainer && $this->symfonyContainer
                                            ->get('kernel')
                                            ->getEnvironment() != 'prod'
            && !$disableAlsoInDev) {
            return;
        }

        if (!is_array($this->metadata)) {
            $this->metadata = array();
        }
        $this->metadata['disableEvent'] = true;
    }

    private function triggerHttpRequestEvent($inputData, $outputData, $originalUrl, $method, $code, $duration)
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

            if (is_array($inputData) && array_key_exists('json', $inputData)) {
                $inputData = $inputData['json'];
            }

            $this->metadata['duration'] = $duration;
            $event = new HttpRequestEvent($inputData, $outputData, $originalUrl, $method, $code, $this->metadata);
            $eventDispatcher = $this->symfonyContainer->get("event_dispatcher");
            $eventDispatcher->dispatch("puremachine.httphelper.request", $event);
        }

        //Remove metadatas
        $this->metadata = array();
    }

    public function addGetParametersToUrl($url, $parameters)
    {
        if (!is_array($parameters)) {
            return $url;
        }

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

    public function getlastAnswerHeaders()
    {
        if (!$this->lastAnswerHeaders) {
            return array();
        }

        return $this->lastAnswerHeaders;
    }

    protected function createException($message, $code=null)
    {
        return new HTTPException($message, $code);
    }

    private function http_parse_headers($raw_headers)
    {
        $headers = array();
        $key = '';

        foreach (explode("\n", $raw_headers) as $i => $h) {
            $h = explode(':', $h, 2);

            if (isset($h[1])) {
                if (!isset($headers[$h[0]]))
                    $headers[$h[0]] = trim($h[1]);
                elseif (is_array($headers[$h[0]])) {
                    $headers[$h[0]] = array_merge($headers[$h[0]], array(trim($h[1])));
                } else {
                    $headers[$h[0]] = array_merge(array($headers[$h[0]]), array(trim($h[1])));
                }

                $key = strtolower($h[0]);
            } else {
                if (substr($h[0], 0, 1) == "\t")
                    $headers[$key] .= "\r\n\t".trim($h[0]);
                elseif (!$key)
                    $headers[0] = trim($h[0]);
            }
        }

        return $headers;
    }
}
