<?php

/**
 *
 */
class wapatternShipping extends waShipping
{
    protected function calculate()
    {
        //TODO put here code to calculate delivery cost
        return 'Calculate method not implemented yet';
    }

    public function allowedCurrency()
    {
        return 'USD';
    }

    public function allowedWeightUnit()
    {
        return 'kg';
    }
}
