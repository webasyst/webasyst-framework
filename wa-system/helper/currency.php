<?php

function wa_currency($n, $currency, $format = '%{s}')
{
    return waCurrency::format($format, $n, $currency);
}