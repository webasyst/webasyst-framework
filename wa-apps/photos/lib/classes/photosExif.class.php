<?php

class photosExif
{
    public static function getInfo($path)
    {
        $fields = array(
            'DateTimeOriginal',
            'Make',
            'Model',
            'ExposureTime',
            'FNumber',
            'ISOSpeedRatings',
            'FocalLength',
            'GPSLongitude',
            'GPSLatitude',
            'Orientation'
        );

        if(function_exists('exif_read_data')) {
            $exif_data = @exif_read_data($path);
        } else {
            $exif_data = array();
        }
        $info = array();
        foreach ($fields as $f) {
            if (isset($exif_data[$f])) {
                $info[$f] = $exif_data[$f];
            }
        }

        if (isset($info['ExposureTime']) && $info['ExposureTime'] && strpos($info['ExposureTime'],'/') !== false){
            list($a, $b) = explode('/', $info['ExposureTime']);
            if ( $a > 1 ) {
                if ( ceil($b/$a) > 0 ) {
                    if ($b/$a > 1) {
                        $info['ExposureTime'] = '1/'.ceil($b/$a);
                    } else {
                        $info['ExposureTime'] = round($a/$b , 1);
                    }
                }
            }
        }
        if (isset($info['FocalLength']) && $info['FocalLength'] && strpos($info['FocalLength'],'/')!==false){

            list($a, $b) = explode('/', $info['FocalLength']);
            if ( $b != 0) {
                $info['FocalLength'] = $a/$b;
            }
        }

        if (isset($info['FNumber']) && $info['FNumber']) {
            $info['FNumber'] = self::exif_get_fstop($info['FNumber']);
        }
        if (isset($info['GPSLatitude']) && $info['GPSLatitude']) {
            $info['GPSLatitude'] = self::exif_get_gps($info['GPSLatitude'], isset($info['GPSLatitudeRef']) && $info['GPSLatitudeRef'] == 'S' ? -1 : 1);
            $info['GPSLongitude'] = self::exif_get_gps($info['GPSLongitude'], isset($info['GPSLongitudeRef']) && $info['GPSLongitudeRef'] == 'W' ? -1 : 1);
        }
        return $info;
    }


    private static function exif_get_gps($coords, $sign)
    {
        $degrees = count($coords) > 0 ? self::exif_get_float($coords[0]) : 0;
        $minutes = count($coords) > 1 ? self::exif_get_float($coords[1]) : 0;
        $seconds = count($coords) > 2 ? self::exif_get_float($coords[2]) : 0;

        return $sign * ($degrees + $minutes / 60 + $seconds / 3600);
    }

    private static function exif_get_float($value)
    {
        $pos = strpos($value, '/');
        if ($pos === false) return (float) $value;
        $a = (float) substr($value, 0, $pos);
        $b = (float) substr($value, $pos + 1);
        return ($b == 0) ? ($a) : ($a / $b);
    }

    private static function exif_get_fstop($value)
    {
        $apex  = self::exif_get_float($value);
        $fstop = pow(2, $apex/2);
        if ($fstop == 0) return false;
        return 'F/' . round($fstop,1);
    }
}
