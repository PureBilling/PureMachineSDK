<?php

namespace PureMachine\Bundle\SDKBundle\Service;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\Common\Annotations\FileCacheReader;
use Doctrine\Common\Annotations\AnnotationReader;
use PureMachine\Bundle\SDKBundle\Store\Base\BaseStore;
use PureMachine\Bundle\SDKBundle\Store\Base\SymfonyBaseStore;

class StoreFactory implements ContainerAwareInterface
{
    protected $container = null;
    protected $annotationReader = null;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function getContainer()
    {
        return $this->container;
    }

    protected function getAnnotationReader()
    {
        if ($this->annotationReader) return $this->annotationReader;

        $cacheDir = $this->getContainer()->getParameter("kernel.cache_dir")
                    .DIRECTORY_SEPARATOR . 'puremachine_annotations';
         $debug = $this->getContainer()->get('kernel')->isDebug();
         $this->annotationReader = new FileCacheReader(new AnnotationReader(),
                                                   $cacheDir,
                                                   $debug);

        return $this->annotationReader;
    }

    public function attachSymfony(BaseStore $store)
    {
        $store->setValidator($this->getContainer()->get('validator'));
        if ($store instanceof ContainerAwareInterface)
            $store->setContainer($this->getContainer());

        if ($store instanceof SymfonyBaseStore)
            $store->setAnnotationReader ($this->getAnnotationReader());

        return $store;
    }

}
