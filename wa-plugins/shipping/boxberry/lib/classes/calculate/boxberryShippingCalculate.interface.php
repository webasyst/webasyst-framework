<?php

/**
 * Interface boxberryShippingCalculate
 */
interface boxberryShippingCalculate
{
    /**
     * @return array
     */
    public function getVariants();

    /**
     * @return string
     */
    public function getPrefix();
}