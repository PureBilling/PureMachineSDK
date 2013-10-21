<?php

namespace PureMachine\Bundle\SDKBundle\Service;

use JMS\DiExtraBundle\Annotation\Service;

/**
 * @Service("pure_machine.sdk.string_helper")
 */
class StringHelper
{
    public static function contains($haystack, $needle, $case = true, $pos = 0)
    {
        if ($case) {
            $result = (strpos($haystack, $needle, 0) === $pos);
        } else {
            $result = (stripos($haystack, $needle, 0) === $pos);
        }

        return $result;
    }

    public static function strBetween($str,$start,$end)
    {
        if (preg_match_all('/' . preg_quote($start) . '(.*?)' . preg_quote($end) . '/',$str,$matches)) {
            return $matches[1];
        }
        // no matches
        return false;
    }

    /**
     *
     * @param string  $needle   string to search in
     * @param string  $haystack string that has to be at starts.
     * @param boolean $case     if true, case sensitive.
     */
    public static function startsWith($haystack, $needle, $case = true)
    {
        return self::contains($haystack, $needle, $case, 0);
    }

    public static function endsWith($haystack, $needle, $case = true)
    {
        return self::contains($haystack, $needle, $case, (strlen($haystack) - strlen($needle)));
    }
}
