<?php

/**
 * Interface boxberryShippingCalculateInterface
 */
interface boxberryShippingCalculateInterface
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
