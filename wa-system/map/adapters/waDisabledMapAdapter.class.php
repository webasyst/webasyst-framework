<?php

class waDisabledMapAdapter extends waMapAdapter
{

    public function getJs($html = true)
    {
        return '';
    }

    /**
     * @param array|string $address
     * @param string array $options
     * @return string
     */
    protected function getByAddress($address, $options = array())
    {
        return '';
    }

    /**
     * @param float $lat
     * @param float $lng
     * @param float array $options
     * @return string
     */
    protected function getByLatLng($lat, $lng, $options = array())
    {
        return '';
    }

    public function getName()
    {
        return 'Disabled map adapter';
    }
}
