<?php

namespace App\Service\Helpers;

class CrawlerHelper
{
    function getSubstringBetweenTwoSubstrings($string, $start, $end)
    {
        $substringStart = strpos($string, $start);
        $substringStart += strlen($start);
        $size = strpos($string, $end, $substringStart) - $substringStart;

        return substr($string, $substringStart, $size);
    }
}