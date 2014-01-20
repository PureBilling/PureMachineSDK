<?php

namespace PureMachine\Bundle\SDKBundle\Service;

use PureMachine\Bundle\SDKBundle\Store\LogStore;
use PureMachine\Bundle\SDKBundle\Exception\HTTPException;

class HttpHelper
{
    private $log= null;

    public function __construct($logActivity=false)
    {
        if ($logActivity) $this->resetLog();
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

    public function getJsonResponse($url, $data, $method='POST',
                                 $headers=array(), $authenticationToken=null)
    {
        $log = $this->log;

        if ($log) $log->getTitle("call: $url");

        $urlParameters = "json=" . json_encode($data);
        $getUrl = $this->getFullUrl($url, $data);

        if ($log) {
            $log->addMessage('method', $method);
            $log->addMessage('called URL', $url);
            $log->addMessage("$method values", json_encode($data));
            $log->addMessage('debug URL (rebuilded)', $getUrl);
        }

        $ch = curl_init();

        if (($method == 'POST')) {
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $urlParameters);
        } else {
            curl_setopt($ch, CURLOPT_URL, $getUrl);
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch,CURLOPT_USERAGENT,'PureMachine HttpHelpers:getJsonAnswer');

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
            $e->addMessage('debug URL (rebuilded)', $getUrl);
            $e->addMessage('data sent:', $data);
            throw $e;
        }

        if ($statusCode == 404) {
            $e = new HTTPException("HTTP error :" . $statusCode ." for ". $url
                                  ." . Page or service not found.",
                                  HTTPException::HTTP_404);
            $e->addMessage('Ouptut', $output);
            $e->addMessage('called URL', $url);
            $e->addMessage('debug URL (rebuilded)', $getUrl);
            $e->addMessage('data sent:', $data);
            throw $e;
        }

        if ($statusCode == 401) {
            $e = new HTTPException("HTTP error :" . $statusCode ." for ". $url
                                  ." . Invalid credentials.",
                                   HTTPException::HTTP_401);
            $e->addMessage('Ouptut', $output);
            $e->addMessage('called URL', $url);
            $e->addMessage('debug URL (rebuilded)', $getUrl);
            $e->addMessage('data sent:', $data);
            throw $e;
        }

        if ($statusCode != 200) {
            $errorMessage = "HTTP error :" . $statusCode . " for $url";
            $e = new HTTPException($errorMessage);
            $e->addMessage('Ouptut', $output);
            $e->addMessage('called URL', $url);
            $e->addMessage('debug URL (rebuilded)', $getUrl);
            $e->addMessage('data sent:', $data);
            throw $e;
        }

        $json = json_decode($output);

        if ($json == null) {
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

    public function addGetParametersToUrl($url, $parameters)
    {
        $frag = parse_url($url);
        $queryStringArray = array();

        if (isset($frag['query'])) {
            parse_str($frag['query'], $queryStringArray);
        }

        $queryString = http_build_query(array_merge($queryStringArray, $parameters));
        return $frag['scheme']. '://' . $frag['host']. $frag['path'] ."?" . $queryString;
    }
}
