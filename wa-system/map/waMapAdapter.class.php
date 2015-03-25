<?php

abstract class waMapAdapter
{

    /**
     * @param string|array $address - string or array(LAT, LNG)
     * @param array $options - map options
     *             'width' => '50%',
     *             'height' => '200px',
     *             'zoom' => 12
     * @return string
     */
    public function getHTML($address, $options = array())
    {
        if (!$address) {
            return '';
        }
        if (is_string($address)) {
            return $this->getByAddress($address, $options);
        } elseif (is_array($address) && isset($address[0]) && isset($address[1])) {
            return $this->getByLatLng($address[0], $address[1], $options);
        }
    }

    /**
     * @return string
     */
    public function getId()
    {
        $class = get_class($this);
        return substr($class, 0, -3);
    }

    /**
     * @return string
     */
    public function getName()
    {
        $class = get_class($this);
        return ucfirst(substr($class, 0, -3));
    }

    /**
     * @return array
     */
    public function getLocale()
    {
        return array();
    }

    /**
     * @param string array $options
     * @return string
     */
    abstract protected function getByAddress($address, $options = array());

    /**
     * @param float lng
     * @param float array $options
     * @return string
     */
    abstract protected function getByLatLng($lat, $lng, $options = array());
}