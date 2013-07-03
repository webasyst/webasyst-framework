<?php

class uspsServices
{
    const TYPE_DOMESTIC = 'Domestic';
    const TYPE_INTERNATIONAL = 'International';

    public static function getServicesFiltered($type = '', $filter = array())
    {
        $services = array();
        $filter_map = array_flip($filter);
        foreach (self::getServices($type) as $s) {
            if (isset($filter_map[$s['id']])) {
                $services[] = $s;
            }
        }

        return $services;
    }

    public static function getServices($type = '')
    {
        $services = self::getAllServices();
        $types = self::getTypesServicesMap();

        if ($type && !in_array($type, array_keys($types))) {
            return array();
        }

        if (!$type) {
            return $services;
        } else {
            $result = array();
            foreach ($types[$type] as $k) {
                $result[$k] = $services[$k];
            }
            return $result;
        }
    }

    public static function getServiceByCode($code)
    {
        foreach (self::getAllServices() as $service) {
            if ($code == $service['code']) {
                return $service;
            }
        }
        return null;
    }

    private static function getTypesServicesMap()
    {
        return array(
            self::TYPE_DOMESTIC      => array(1, 2, 3, 4, 5, 6),
            self::TYPE_INTERNATIONAL => array(7, 8, 9, 10, 11)
        );
    }

    public static function getTypeByCountry($code)
    {
        if (strtoupper($code) == 'USA') {
            return self::TYPE_DOMESTIC;
        } else {
            return self::TYPE_INTERNATIONAL;
        }
    }


    /**
     * @param string $service_id
     * @return string type @see const declarations
     */
    public static function getServiceType($service_id)
    {
        foreach (self::getTypesServicesMap() as $type => $services) {
            if (in_array($service_id, $services)) {
                return $type;
            }
        }
        return '';
    }

    private static function getAllServices()
    {
        return array(
            1 => array(
                    'id'           => 1,
                    'name'         => 'Express',
                    'code'         => 'Express',
                    'maxWeight'    => array(
                            'lbs' => '70',
                            'oz'  => '0',
                    ),
                    'packageSizes' => array(
                            'Regular',
                            'Large'
                    ),
            ),
            2 => array(
                    'id'                 => 2,
                    'name'               => 'First Class',
                    'code'               => 'First Class',
                    'maxWeight'          => array(
                            'lbs' => '0',
                            'oz'  => '13',
                    ),
                    'packageSizes'       => array(
                            'Regular',
                            'Large'
                    ),
                    'FirstClassMailType' => array(
                            'Letter',
                            'Flat',
                            'Parcel',
                            'Postcard'
                    ),
            ),
            3 => array(
                    'id'           => 3,
                    'name'         => 'Priority',
                    'code'         => 'Priority',
                    'maxWeight'    => array(
                            'lbs' => '70',
                            'oz'  => '0',
                    ),
                    'packageSizes' => array(
                            'Regular',
                            'Large'
                    ),
            ),
            4 => array(
                    'id'           => 4,
                    'name'         => 'Standard Post',
                    'code'         => 'Standard Post',
                    'maxWeight'    => array(
                            'lbs' => '70',
                            'oz'  => '0',
                    ),
                    'packageSizes' => array(
                            'Regular',
                            'Large',
                            'Oversize',
                    ),
            ),
            5 => array(
                    'id'           => 5,
                    'name'         => 'Media Mail',
                    'code'         => 'Media',
                    'maxWeight'    => array(
                            'lbs' => '70',
                            'oz'  => '0',
                    ),
                    'packageSizes' => array(
                            'Regular',
                            'Large'
                    ),
            ),
            6 => array(
                    'id'           => 6,
                    'name'         => 'Library Mail',
                    'code'         => 'Library',
                    'maxWeight'    => array(
                            'lbs' => '70',
                            'oz'  => '0',
                    ),
                    'packageSizes' => array(
                            'Regular',
                            'Large'
                    ),
            ),
            7 => array(
                    'id'           => 7,
                    'name'         => 'Package',
                    'code'         => 'Package',
                    'maxWeight'    => array(
                            'lbs' => '70',
                            'oz'  => '0',
                    ),
                    'packageSizes' => array(
                    ),
            ),
            8 => array(
                    'id'           => 8,
                    'name'         => 'Postcards or aerogrammes',
                    'code'         => 'Postcards or aerogrammes',
                    'maxWeight'    => array(
                            'lbs' => '70',
                            'oz'  => '0',
                    ),
                    'packageSizes' => array(
                    ),
            ),
            9 => array(
                    'id'           => 9,
                    'name'         => 'Envelope',
                    'code'         => 'Envelope',
                    'maxWeight'    => array(
                            'lbs' => '70',
                            'oz'  => '0',
                    ),
                    'packageSizes' => array(
                    ),
            ),
            10 => array(
                    'id'           => 10,
                    'name'         => 'LargeEnvelope',
                    'code'         => 'LargeEnvelope',
                    'maxWeight'    => array(
                            'lbs' => '70',
                            'oz'  => '0',
                    ),
                    'packageSizes' => array(
                    ),
            ),
            11 => array(
                    'id'           => 11,
                    'name'         => 'FlatRate',
                    'code'         => 'FlatRate',
                    'maxWeight'    => array(
                            'lbs' => '70',
                            'oz'  => '0',
                    ),
                    'packageSizes' => array(
                    ),
            ),
        );
    }
}