<?php

function wa_date($format, $time = null, $timezone = null, $locale = null)
{
    return waDateTime::format($format, $time, $timezone, $locale);
}

function wa_parse_date($format, $string, $timezone = null, $locale = null)
{
    return waDateTime::parse($format, $string, $timezone, $locale);
}