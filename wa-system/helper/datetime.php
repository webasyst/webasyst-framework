<?php

function wa_date($format, $time = null, $timezone = null, $locale = null)
{
    try {
        return waDateTime::format($format, $time, $timezone, $locale);
    } catch (Exception $e) {
        return '';
    }
}

function wa_parse_date($format, $string, $timezone = null, $locale = null)
{
    try {
        return waDateTime::parse($format, $string, $timezone, $locale);
    } catch (Exception $e) {
        return '';
    }
}