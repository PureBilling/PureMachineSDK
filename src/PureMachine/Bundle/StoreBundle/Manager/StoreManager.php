<?php

namespace PureMachine\Bundle\StoreBundle\Manager;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\FileCacheReader;
use Symfony\Component\Validator\Validation;

class StoreManager
{
    private static $defaultAdapter;
    private static $cacheDirectory;
    private static $annotationReader;
    private static $validator;
    private static $debug = false;

    public static function setDebug($debug)
    {
        static::$debug = $debug;
    }

    public static function setDefaultAdapter($adapter)
    {
        static::$defaultAdapter = $adapter;
    }

    public static function getAdapter($storeInstanceOrClassName)
    {
        return static::$defaultAdapter;
    }

    public static function setCacheDirectory($cacheDir)
    {
        $dir = $cacheDir . DIRECTORY_SEPARATOR . "store_cache";
        if (!file_exists($dir)) {
            mkdir($dir);
        }

        static::$cacheDirectory = $dir;
    }

    public static function getCacheDirectory()
    {
        if (!static::$cacheDirectory) {
            static::setCacheDirectory(sys_get_temp_dir());
        }

        return static::$cacheDirectory;
    }

    public static function setAnnotationReader($annotationReader)
    {
        static::$annotationReader = $annotationReader;
    }

    public static function getAnnotationReader()
    {
        if (!static::$annotationReader) {
            static::$annotationReader = new FileCacheReader(
                                            new AnnotationReader(),
                                            static::getCacheDirectory(),
                                            static::$debug
                                        );
        }

        return static::$annotationReader;
    }

    public static function setValidator($validator)
    {
        static::$validator = $validator;
    }

    public static function getValidator()
    {
        if (!static::$validator) {
            static::$validator = Validation::createValidatorBuilder()
                                    ->enableAnnotationMapping(static::getAnnotationReader())
                                    ->getValidator();
        }

        return static::$validator;
    }
}
