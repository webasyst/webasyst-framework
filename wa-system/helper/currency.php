<?php

function wa_currency($n, $currency, $format = '%{s}')
{
    return waCurrency::format($format, $n, $currency);
}

function wa_currency_html($n, $currency, $format = '%{h}')
{
    return waCurrency::format($format, $n, $currency);
}