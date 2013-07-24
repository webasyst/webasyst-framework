<?php

class cashPayment extends waPayment
{
    public function allowedCurrency()
    {
        return true;
    }
}
