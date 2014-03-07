<?php

namespace PureMachine\Bundle\SDKBundle\Service;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\Common\Annotations\FileCacheReader;
use Doctrine\Common\Annotations\AnnotationReader;
use PureMachine\Bundle\SDKBundle\Event\WebServiceCalledEvent;
use PureMachine\Bundle\SDKBundle\Event\WebServiceCallingEvent;
use PureMachine\Bundle\SDKBundle\Store\Base\JsonSerializable;
use PureMachine\Bundle\SDKBundle\Exception\WebServiceException;
use PureMachine\Bundle\SDKBundle\Exception\Exception;
use PureMachine\Bundle\SDKBundle\Exception\HTTPException;
use PureMachine\Bundle\SDKBundle\Store\Base\BaseStore;
use PureMachine\Bundle\SDKBundle\Store\WebService\Response;
use PureMachine\Bundle\SDKBundle\Store\WebService\ArrayResponse;
use PureMachine\Bundle\SDKBundle\Store\WebService\ErrorResponse;
use PureMachine\Bundle\SDKBundle\Store\WebService\DebugErrorResponse;
use PureMachine\Bundle\SDKBundle\Store\Base\StoreHelper;
use Symfony\Component\Validator\Validation;

class WebServiceClient implements ContainerAwareInterface
{

    const MAJOR_VERSION_VALIDATION_SUPPORT = 5;
    const MINOR_VERSION_VALIDATION_SUPPORT = 3;
    const RELEASE_VERSION_VALIDATION_SUPPORT = 10;

    protected $container = null;
    protected $annotationReader = null;
    protected $validator = null;
    protected $login = null;
    protected $password = null;
    protected $endPoint = null;

    public function __construct($endPoint = null)
    {
        $this->endPoint = $endPoint;
    }

    /**
     * Symfony2 container
     * Called only if we are in a Symfony2 context.
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function getContainer()
    {
        return $this->container;
    }

    public function isSymfony()
    {
        if ($this->container) return true;
        return false;
    }

    protected function getAnnotationReader()
    {

        if ($this->annotationReader) return $this->annotationReader;

        if ($this->isSymfony()) {
            $cacheDir = $this->getContainer()->getParameter("kernel.cache_dir")
                       .DIRECTORY_SEPARATOR . 'puremachine_annotations';
            $debug = $this->getContainer()->get('kernel')->isDebug();
            $this->annotationReader = new FileCacheReader(
                    new AnnotationReader(),
                    $cacheDir,
                    $debug
                    );
        } else {
            $this->annotationReader = new AnnotationReader();
        }

        return $this->annotationReader;
    }

    /**
     * Call a webService
     *
     * If the webService is local, make the call without
     * doing any HTTP call
     */
    public function call($webServiceName, $inputData=null,
                              $version='V1')
    {
        //Create unique token to identify the call
        $token = uniqid("CALL_");
        if ($this->isSymfony()) {
            $eventDispatcher = $this->container->get("event_dispatcher");
        }

        /*
         * Throw initial event before executing
         * the call, remote or local
         */
        if (($inputData instanceof BaseStore) && $this->isSymfony()) {
            $event = new WebServiceCallingEvent($token, $webServiceName, $inputData, $version);
            $eventDispatcher->dispatch("puremachine.webservice.calling", $event);
        }

        //check if the service is local
        if ($this->isSymfony() && $this->container->has('pureMachine.sdk.webServiceManager')) {
            $WebServiceManager = $this->container->get('pureMachine.sdk.webServiceManager');

            if ($WebServiceManager->getSchema($webServiceName)) {
                $return = $WebServiceManager->localCall($webServiceName, $inputData, $version);

                /*
                 * Throw post event after executing
                 * the local call
                 */
                if (($return instanceof BaseStore) && $this->isSymfony()) {
                    $event = new WebServiceCalledEvent($token, $webServiceName, $return, $version, true);
                    $eventDispatcher->dispatch("puremachine.webservice.called", $event);
                }

                return $return;
            }
        }

        //We did not found the webService in local, we do it remotely.
        $return = $this->remoteCall($webServiceName, $inputData, $version);

        /*
         * Throw post event after executing
         * the local call
         */
        if (($return instanceof BaseStore) && $this->isSymfony()) {
            $event = new WebServiceCalledEvent($token, $webServiceName, $return, $version, false);
            $eventDispatcher->dispatch("puremachine.webservice.called", $event);
        }

        return $return;
    }

    protected function remoteCall($webServiceName, $inputData, $version)
    {
        //Try to lookup The webService URL
        try {
            $url = $this->buildRemoteUrl($webServiceName, $version);
        } catch (WebServiceException $e) {
            return $this->buildErrorResponse($webServiceName, $version, $e);
        }

        //Handle special mapping :
        //Simple type are mapped to Store classes
        $inputData = StoreHelper::simpleTypeToStore($inputData);

        /*
         * Validate input value - Only if the PHP version is
         * greater or equal than 5.3.10 (previous version does not work
         * correctly with annotation validations)
         */
        if(
            (PHP_MAJOR_VERSION >= static::MAJOR_VERSION_VALIDATION_SUPPORT)
            && (PHP_MINOR_VERSION >= static::MINOR_VERSION_VALIDATION_SUPPORT)
            && (PHP_RELEASE_VERSION >= static::RELEASE_VERSION_VALIDATION_SUPPORT)
            )
        {
            try {
                $this->checkType($inputData, null, null,
                    WebServiceException::WS_003);
            } catch (WebServiceException $e) {
                return $this->buildErrorResponse($webServiceName, $version, $e);
            }
        }

        //Validate and serialize input value
        try {
            $inputData = StoreHelper::serialize($inputData);
        } catch (Exception $e) {
            return $this->buildErrorResponse($webServiceName, $version, $e);
        }

        //Make the http call
        if ($this->isSymfony()) {
            $http = $this->container->get('pure_machine.sdk.http_helper');
        } else {
            $http = new HttpHelper();
        }

        list($login, $password) = $this->getCredentials();
        $fullUrl = $http->getFullUrl($url, $inputData);

        try {
            $http->setNextRequestEventMetadata(array(
                'disableEvent' => true
            ));
            $response = $http->getJsonResponse(
                    $url,
                    $inputData,
                    'POST',
                    array(),
                    $login . ":" . $password
                    );
        } catch (HTTPException $e) {
            return $this->buildErrorResponse($webServiceName, $version, $e, $fullUrl);
        }

        //Cast $inputValue if needed
        try {
            $response = StoreHelper::unSerialize(
                    $response,
                    array(),
                    $this->getAnnotationReader(),
                    $this->getContainer()
                    );
            } catch (Exception $e) {
                return $this->buildErrorResponse($webServiceName, $version, $e, $fullUrl);
        }

        return $response;
    }

    //FIXME: it's symfony2 specific. need to fix it.
    protected function buildRemoteUrl($webServiceName, $version)
    {
        /*
         * The namespaces can be achieved through the Symfony2
         * container if defined, or as fallback through the configuration
         * specified on the static class PureBilling
         */
        $baseUrl = null;
        if ($this->isSymfony() && $this->container->hasParameter('ws_namespaces')) {
            $namespaces = $this->container->getParameter('ws_namespaces');
            $stringHelper = $this->container->get('pure_machine.sdk.string_helper');
            natsort($namespaces);

            foreach ($namespaces as $namespace => $url) {
                if ($stringHelper->startsWith($webServiceName, $namespace, false)) {
                    $baseUrl = $url;
                    break;
                }
            }
        } else {
            /*
             * Using fallback on static \PureBilling
             * configuration
             */
            if ($this->endPoint) {
                $baseUrl = $this->endPoint;
            } elseif (!class_exists('\PureBilling') || \PureBilling::getEndPoint()) {
                $baseUrl = \PureBilling::getEndPoint();
            } else {
                throw new WebServiceException('You need to pass the API enpoint using '
                        ."class contructor", WebServiceException::WS_005);
            }

        }

        if (!$baseUrl) {
            throw new WebServiceException("Can't find remote server in config for webService "
                                     .$webServiceName, WebServiceException::WS_005);
        }

        return "$baseUrl/$version/$webServiceName";
    }

    protected function getValidator()
    {
        if ($this->validator) return $this->validator;

        if ($this->isSymfony())
            $this->validator = $this->container->get('validator');
        else {
            $this->validator = Validation::createValidatorBuilder()
                                          ->enableAnnotationMapping()
                                          ->getValidator();
        }

        return $this->validator;
    }

    /**
     * Check json schema type validity using symfony2 Validation system
     * return the error message if any or false
     *
     * @param string $value value to check
     * @param string $type  type to check
     */
    protected function checkType($value, $type, $allowedClassNames, $errorCode)
    {
        //We only accept object of array
        if ($type && $value && gettype($value) != $type)
            throw new WebServiceException("Should be a $type"
                                         .", but it's a " . gettype($value), $errorCode);

        if (!$value && count($allowedClassNames) >0) {
            throw new WebServiceException("Should a instance of " . json_encode($allowedClassNames)
                                         .", but it's null ", $errorCode);
        }

        //check if the value implement JsonSerializable if needed
        if ($this->needToImplementJsonSerializable($value) &&
            !($value instanceof JsonSerializable)) {
            throw new WebServiceException("object of type ".get_class($value)." has to implement "
                                         ."PureMachine\Bundle\SDKBundle\Store\JsonSerializable "
                                         ."Interface",
                                          $errorCode);
        }

        //If value is store, we validate it
        if ($value instanceof BaseStore) {
            //Check if the type if fine if defined
            if(count($allowedClassNames) >0 &&
               !in_array(get_class($value), $allowedClassNames)) {
                throw new WebServiceException("your store should be a intance of "
                                             . json_encode($allowedClassNames)
                                             .", but it's a " . get_class($value),
                                             $errorCode);
            }

            //Validate the store
            if (!$value->validate($this->getValidator())) {
                $violations = $value->getViolations();
                $property = $violations[0]->getPropertyPath();
                $message = "Store validation: "
                          .$violations[0]->getMessage() ." for property '"
                          .$property ."' in "
                          .get_class($value);

                $propertyAssesor = "get" . ucfirst($property);

                if (property_exists($value, $propertyAssesor)) {
                    $propValue = $value->$propertyAssesor();
                } else $propValue = '?';

                $message .= ". value(type:" .gettype($propValue). ")";
                if (is_scalar($propValue)) $message .= "='$propValue'";
                throw new WebServiceException($message, $errorCode);
            }
        //We have an array of object, and potentiallt stores
        //We need to check the type and validate stores
        } elseif ($type == 'array' && is_array($allowedClassNames) && count($allowedClassNames) > 0) {
            foreach ($value as $store) {
                $this->checkType($store, 'object', $allowedClassNames, $errorCode);
            }
        }
    }

    protected function buildErrorResponse($webServiceName, $version, Exception $exception,
                                      $fullUrl=null, $serialize=false)
    {
        $data = $exception->getStore();

        if ($serialize) $data = $data->serialize();

        /**
         * If we are in production environement, we remove the stack trace
         * and detailled error messages
         */
        if (!$this->isSymfony() || $this->container->get('kernel')->getEnvironment() == 'prod') {
            $data->setStack(array());
            $data->setMessages(array());
        }

        return $this->buildResponse($webServiceName, $version, $data, $fullUrl, 'error');
    }

    protected function buildResponse($webServiceName, $version, $data, $fullUrl=null, $status='success')
    {
        if ($status == 'success') {
            if (is_array($data)) {
                $response = new ArrayResponse();
            } else {
                $response = new Response();
            }
        } elseif ($this->container && $this->container->get('kernel')->getEnvironment() != 'prod') {
            $response = new DebugErrorResponse();
        } else {
            $response = new ErrorResponse();
        }

        $response->setWebService($webServiceName);
        $response->setStatus($status);
        $response->setVersion($version);
        $response->setLocal(true);
        $response->setAnswer($data);

        if ($response instanceof DebugErrorResponse) {
            $response->setUrl($fullUrl);
        }

        if ($status == 'success') $response->response = $data;
        else $response->error = $data;
        return $response;
    }

    public function needToImplementJsonSerializable($value)
    {
        if (!is_object($value)) return false;
        if ($value instanceof \stdClass) return false;
        $class = get_class($value);
        if ($class=='DateTime' || $class=='stdClass') return false;
        return true;
    }

    public function setCredentials($login, $password)
    {
        $this->login = $login;
        $this->password = $password;
    }

    /**
     * Should be used only for local resolution
     * in remote, use the Symfony firewall.
     */
    public function getCredentials()
    {
        if ($this->login) return array( $this->login, $this->password);

        //Using static PureBilling as fallback
        if (class_exists('\PureBilling') && \PureBilling::getPrivateKey()) {
            return array('api', \PureBilling::getPrivateKey());
        }
    }
}
