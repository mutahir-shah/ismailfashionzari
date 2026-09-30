<?php

if (!function_exists('getDateFormat')) {
    function getDateFormat()
    {
        static $format;

        if (!$format) {
            $format = optional(\App\Models\GeneralSetting::first())->date_format ?? 'd-m-Y';
        }

        return $format;
    }
}
